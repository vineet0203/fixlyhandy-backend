<?php
// app/Services/File/FileAttachmentService.php

namespace App\Services\File;

use App\Models\TemporaryUpload;
use App\Services\Upload\FileUploadService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FileAttachmentService
{
    private string $permanentDisk = 'local'; // Changed from 'public' to 'local'
    private string $privatePathPrefix = 'private/'; // Prefix for private files

    public function __construct(
        private FileUploadService $uploadService
    ) {}

    /**
     * Process and attach a file from temporary upload
     */
    public function attachFile(
        array &$data,
        string $tempIdField,
        string $pathField,
        string $destinationPath,
        array $allowedMimeTypes = [],
        ?int $maxSizeKb = null,
        ?string $customFilename = null,
        bool $keepOriginalName = false
    ): array {
        $errors = [];

        Log::info('=== FILE ATTACHMENT START ===', [
            'tempIdField' => $tempIdField,
            'pathField' => $pathField,
            'destinationPath' => $destinationPath,
            'temp_id' => $data[$tempIdField] ?? null,
        ]);

        // Check if temp_id exists
        if (empty($data[$tempIdField])) {
            Log::info('No temp_id found in field', ['field' => $tempIdField]);
            unset($data[$tempIdField]);
            return $errors;
        }

        $tempId = $data[$tempIdField];
        Log::info('Processing temp_id', ['temp_id' => $tempId]);

        try {
            // Validate temporary upload exists
            $tempUpload = TemporaryUpload::where('temp_id', $tempId)
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->first();

            if (!$tempUpload) {
                Log::warning('Temporary upload not found or invalid', [
                    'temp_id' => $tempId,
                ]);

                $errors[$tempIdField] = ['Invalid or expired temporary upload.'];
                return $errors;
            }

            Log::info('Temporary upload found', [
                'temp_id' => $tempUpload->temp_id,
                'original_name' => $tempUpload->original_name,
                'mime_type' => $tempUpload->mime_type,
                'size' => $tempUpload->size,
            ]);

            // Validate file type
            if (!empty($allowedMimeTypes) && !in_array($tempUpload->mime_type, $allowedMimeTypes)) {
                $allowedTypesStr = implode(', ', $allowedMimeTypes);
                $errors[$tempIdField] = [
                    "File type not allowed. Allowed: {$allowedTypesStr}. Uploaded: {$tempUpload->mime_type}"
                ];
                Log::warning('File type validation failed', $errors[$tempIdField]);
                return $errors;
            }

            // Validate file size
            if ($maxSizeKb !== null && $tempUpload->size > ($maxSizeKb * 1024)) {
                $maxSizeMb = round($maxSizeKb / 1024, 2);
                $actualSizeMb = round($tempUpload->size / (1024 * 1024), 2);
                $errors[$tempIdField] = [
                    "File is too large. Maximum: {$maxSizeMb}MB, Actual: {$actualSizeMb}MB"
                ];
                Log::warning('File size validation failed', $errors[$tempIdField]);
                return $errors;
            }

            // Generate filename
            $filename = $this->generateFilename(
                $tempUpload,
                $customFilename,
                $keepOriginalName
            );

            Log::info('Generated filename', [
                'filename' => $filename,
            ]);

            // Add private/ prefix to destination path
            $privateDestinationPath = $this->privatePathPrefix . ltrim($destinationPath, '/');

            Log::info('Calling finalizeUpload to private directory', [
                'tempId' => $tempId,
                'originalDestination' => $destinationPath,
                'privateDestination' => $privateDestinationPath,
                'filename' => $filename,
            ]);

            // Finalize the upload to private directory
            $finalized = $this->uploadService->finalizeUpload(
                $tempId,
                $privateDestinationPath,
                $filename
            );

            Log::info('File finalized to private storage', [
                'finalized_data' => $finalized,
                'final_path' => $finalized['final_path'] ?? null,
            ]);

            // Store permanent path and remove temp_id
            $data[$pathField] = $finalized['final_path'];
            unset($data[$tempIdField]);

            Log::info('File attached successfully to private storage', [
                'temp_id' => $tempId,
                'field' => $tempIdField,
                'path_field' => $pathField,
                'final_path' => $finalized['final_path'],
                'destination' => $privateDestinationPath,
            ]);

            return $errors;
        } catch (\Exception $e) {
            Log::error('Failed to attach file to private storage', [
                'temp_id' => $tempId,
                'field' => $tempIdField,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errors[$tempIdField] = ["Failed to process file upload: {$e->getMessage()}"];
            return $errors;
        }
    }

    /**
     * Update an existing file attachment
     */
    public function updateFile(
        $model,
        array &$data,
        string $tempIdField,
        string $pathField,
        string $destinationPath,
        array $allowedMimeTypes = [],
        ?int $maxSizeKb = null,
        ?string $customFilename = null,
        bool $keepOriginalName = false,
        string $removeField = 'remove_file'
    ): array {
        $errors = [];
        $removeFile = $data[$removeField] ?? false;
        $hasNewFile = !empty($data[$tempIdField]);

        // Case 1: Remove existing file
        if ($removeFile) {
            $this->deleteFile($model->$pathField);
            $data[$pathField] = null;
            unset($data[$removeField]);
            return $errors;
        }

        // Case 2: New file uploaded
        if ($hasNewFile) {
            // Delete old file if exists
            if ($model->$pathField) {
                $this->deleteFile($model->$pathField);
            }

            // Add private/ prefix to destination path
            $privateDestinationPath = $this->privatePathPrefix . ltrim($destinationPath, '/');

            // Attach new file to private storage
            $errors = $this->attachFile(
                $data,
                $tempIdField,
                $pathField,
                $privateDestinationPath, // Use private path
                $allowedMimeTypes,
                $maxSizeKb,
                $customFilename,
                $keepOriginalName
            );

            return $errors;
        }

        // Case 3: No change to file
        unset($data[$tempIdField]);
        return $errors;
    }

    /**
     * Delete file from private storage
     */
    public function deleteFile(?string $filePath): bool
    {
        if (empty($filePath)) {
            return false;
        }

        try {
            // Files are on local disk (private directory)
            if (Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
                Log::info('File deleted from private storage', ['path' => $filePath]);
                return true;
            }

            // Also check if it exists without private prefix (for backward compatibility)
            if (str_starts_with($filePath, 'private/')) {
                $relativePath = substr($filePath, 8); // Remove 'private/' prefix
                if (Storage::disk('local')->exists($relativePath)) {
                    Storage::disk('local')->delete($relativePath);
                    Log::info('File deleted from private storage (without prefix)', ['path' => $relativePath]);
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to delete file from private storage', [
                'path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get signed URL for file (from private storage)
     */
    public function getSignedUrl(
        ?string $filePath,
        int $expirationMinutes = 60
    ): ?array {
        if (empty($filePath)) {
            return null;
        }

        try {
            // Always use generateTemporarySignedUrl for private files
            return $this->uploadService->getPermanentSignedUrl($filePath, $expirationMinutes);
        } catch (\Exception $e) {
            Log::error('Failed to get signed URL for private file', [
                'path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate filename
     */
    private function generateFilename(
        TemporaryUpload $tempUpload,
        ?string $customFilename = null,
        bool $keepOriginalName = false
    ): string {
        if ($customFilename) {
            return $customFilename;
        }

        if ($keepOriginalName) {
            // Sanitize original name
            $originalName = pathinfo($tempUpload->original_name, PATHINFO_FILENAME);
            $extension = pathinfo($tempUpload->original_name, PATHINFO_EXTENSION);
            $safeName = Str::slug($originalName);
            return $safeName . '_' . Str::random(8) . '.' . $extension;
        }

        // Generate random filename
        $extension = pathinfo($tempUpload->original_name, PATHINFO_EXTENSION);
        $timestamp = time();
        $random = Str::random(10);
        return "file_{$random}_{$timestamp}.{$extension}";
    }


    /**
     * Validate temporary upload
     */
    public function validateTemporaryUpload(
        ?string $tempId,
        array $allowedMimeTypes = [],
        ?int $maxSizeKb = null
    ): array {
        $errors = [];

        if (empty($tempId)) {
            return $errors;
        }

        $tempUpload = TemporaryUpload::where('temp_id', $tempId)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tempUpload) {
            $errors[] = 'Invalid or expired temporary upload.';
            return $errors;
        }

        // Validate file type
        if (!empty($allowedMimeTypes) && !in_array($tempUpload->mime_type, $allowedMimeTypes)) {
            $allowedTypesStr = implode(', ', $allowedMimeTypes);
            $errors[] = "File type not allowed. Allowed: {$allowedTypesStr}. Uploaded: {$tempUpload->mime_type}";
        }

        // Validate file size
        if ($maxSizeKb !== null && $tempUpload->size > ($maxSizeKb * 1024)) {
            $maxSizeMb = round($maxSizeKb / 1024, 2);
            $actualSizeMb = round($tempUpload->size / (1024 * 1024), 2);
            $errors[] = "File is too large. Maximum: {$maxSizeMb}MB, Actual: {$actualSizeMb}MB";
        }

        return $errors;
    }

    /**
     * Clean up unused temporary uploads
     */
    public function cleanupUnusedTemporaryUpload(string $tempId): bool
    {
        try {
            return $this->uploadService->deleteTemporaryUpload($tempId);
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup temporary upload', [
                'temp_id' => $tempId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Batch attach multiple files
     */
    public function attachMultipleFiles(
        array &$data,
        array $fileConfigs,
        string $prefix = ''
    ): array {
        $allErrors = [];

        foreach ($fileConfigs as $config) {
            $tempIdField = $prefix . $config['temp_id_field'];
            $pathField = $prefix . $config['path_field'];

            if (empty($data[$tempIdField])) {
                continue;
            }

            $errors = $this->attachFile(
                $data,
                $tempIdField,
                $pathField,
                $config['destination_path'],
                $config['allowed_types'] ?? [],
                $config['max_size_kb'] ?? null,
                $config['custom_filename'] ?? null,
                $config['keep_original_name'] ?? false
            );

            if (!empty($errors)) {
                $allErrors = array_merge($allErrors, $errors);
            }
        }

        return $allErrors;
    }

    /**
     * Get file info from temporary upload
     */
    public function getFileInfo(string $tempId): ?array
    {
        $tempUpload = TemporaryUpload::where('temp_id', $tempId)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tempUpload) {
            return null;
        }

        return [
            'temp_id' => $tempUpload->temp_id,
            'original_name' => $tempUpload->original_name,
            'mime_type' => $tempUpload->mime_type,
            'size' => $tempUpload->size,
            'size_mb' => round($tempUpload->size / (1024 * 1024), 2),
            'created_at' => $tempUpload->created_at,
            'expires_at' => $tempUpload->expires_at,
        ];
    }
}
