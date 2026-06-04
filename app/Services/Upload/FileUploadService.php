<?php
// app/Services/Upload/FileUploadService.php

namespace App\Services\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\TemporaryUpload;
use Illuminate\Support\Facades\Log;
use App\Services\File\SignedUrlService;

class FileUploadService
{
    private string $tempDisk = 'local';
    private string $tempPath = 'temp/';
    private string $permanentDisk = 'private';

    // Allowed file types
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function __construct(
        private SignedUrlService $signedUrlService
    ) {}

    /**
     * Upload temporary file
     */

    public function uploadTemporaryFile(UploadedFile $file, ?int $userId = null): array
    {
        // Validate file (MIME type only, no size check)
        $this->validateFile($file);

        // Generate unique identifier
        $tempId = $this->generateTempId();
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Generate safe filename
        $safeFileName = $this->generateSafeFileName($originalName, $tempId);

        // Store file
        $filePath = $file->storeAs($this->tempPath, $safeFileName, $this->tempDisk);

        // Clean the file path to remove double slashes
        $filePath = preg_replace('#/+#', '/', $filePath);

        // Create database record
        $tempUpload = TemporaryUpload::create([
            'temp_id' => $tempId,
            'user_id' => $userId,
            'original_name' => $originalName,
            'storage_path' => $filePath,
            'disk' => $this->tempDisk,
            'mime_type' => $mimeType,
            'size' => $fileSize,
            'expires_at' => Carbon::now()->addHours(24),
            'is_used' => false,
        ]);

        Log::info('Temporary file uploaded', [
            'temp_id' => $tempId,
            'user_id' => $userId,
            'file_path' => $filePath,
            'size' => $fileSize,
        ]);

        // Generate signed URL
        $signedUrlData = $this->signedUrlService->generateTemporarySignedUrl($filePath);

        return [
            'success' => true,
            'temp_id' => $tempId,
            'original_name' => $originalName,
            'temp_path' => $filePath,
            'signed_url' => $signedUrlData['url'] ?? null,
            'url_expires_at' => $signedUrlData['expires_at'] ?? null,
            'expires_at' => $tempUpload->expires_at,
            'size' => $fileSize,
            'mime_type' => $mimeType,
        ];
    }

    /**
     * Get signed URL for temporary file (using only generateTemporarySignedUrl)
     */
    public function getTemporarySignedUrl(string $tempId, int $expirationMinutes = 60): ?array
    {
        $tempUpload = TemporaryUpload::where('temp_id', $tempId)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$tempUpload) {
            return null;
        }

        // Use only generateTemporarySignedUrl
        return $this->signedUrlService->generateTemporarySignedUrl(
            $tempUpload->storage_path,
            $expirationMinutes
        );
    }

    /**
     * Get signed URL for file (always use temporary method)
     */
    public function getPermanentSignedUrl(string $path, int $expirationMinutes = 60): ?array
    {
        // Always use generateTemporarySignedUrl
        return $this->signedUrlService->generateTemporarySignedUrl($path, $expirationMinutes);
    }

    /**
     * Get signed URL for file (always use temporary method)
     */
    public function getPrivateSignedUrl(string $path, int $expirationMinutes = 60): ?array
    {
        // Always use generateTemporarySignedUrl
        return $this->signedUrlService->generateTemporarySignedUrl($path, $expirationMinutes);
    }


    /**
     * Get current PHP limits for debugging
     */
    public function getPhpLimits(): array
    {
        $convertToBytes = function (string $size): int {
            $size = trim($size);
            $last = strtolower($size[strlen($size) - 1]);
            $value = intval($size);

            switch ($last) {
                case 'g':
                    $value *= 1024;
                case 'm':
                    $value *= 1024;
                case 'k':
                    $value *= 1024;
            }

            return $value;
        };

        return [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'upload_max_filesize_bytes' => $convertToBytes(ini_get('upload_max_filesize')),
            'post_max_size' => ini_get('post_max_size'),
            'post_max_size_bytes' => $convertToBytes(ini_get('post_max_size')),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time'),
            'max_file_uploads' => ini_get('max_file_uploads'),
        ];
    }

    /**
     * Validate file (MIME type only)
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            $errorCode = $file->getError();
            $errorMessage = $this->getUploadErrorMessage($errorCode);
            throw new \Exception("File upload failed: {$errorMessage}");
        }

        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            $allowedTypes = implode(', ', array_keys($this->getAllowedTypesDescriptions()));
            throw new \Exception(
                "File type '{$file->getMimeType()}' not allowed. " .
                    "Allowed types: {$allowedTypes}"
            );
        }
    }

    /**
     * Get human-readable upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
        ];

        return $errors[$errorCode] ?? "Unknown upload error (code: {$errorCode})";
    }

    /**
     * Finalize temporary upload - move to permanent location (PRIVATE disk)
     */
    public function finalizeUpload(string $tempId, string $destinationPath, ?string $newFileName = null): array
    {
        $tempUpload = TemporaryUpload::where('temp_id', $tempId)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$tempUpload) {
            throw new \Exception('Temporary upload not found, expired, or already used');
        }

        if (!$newFileName) {
            $newFileName = $this->generateSafeFileName(
                $tempUpload->original_name,
                Str::random(10)
            );
        }

        // Ensure destination path starts with 'private/' if not already
        if (!str_starts_with($destinationPath, 'private/')) {
            $destinationPath = 'private/' . ltrim($destinationPath, '/');
        }

        $finalPath = rtrim($destinationPath, '/') . '/' . $newFileName;

        Log::info('=== FINALIZE UPLOAD START ===', [
            'temp_id' => $tempId,
            'temp_path' => $tempUpload->storage_path,
            'destination_path' => $destinationPath,
            'final_path' => $finalPath,
            'permanent_disk' => $this->permanentDisk,
        ]);

        if (Storage::disk($this->tempDisk)->exists($tempUpload->storage_path)) {
            // Copy file to PRIVATE location (not public)
            // Note: We're using 'local' disk for both temp and permanent, just different directories
            $copied = Storage::disk('local')->put(
                $finalPath,
                Storage::disk($this->tempDisk)->get($tempUpload->storage_path)
            );

            if (!$copied) {
                Log::error('Failed to copy file to private location', [
                    'temp_path' => $tempUpload->storage_path,
                    'final_path' => $finalPath,
                ]);
                throw new \Exception('Failed to copy file to permanent location');
            }

            // Verify the file was copied
            if (!Storage::disk('local')->exists($finalPath)) {
                Log::error('File not found after copy', [
                    'final_path' => $finalPath,
                    'disk' => 'local',
                ]);
                throw new \Exception('File was not copied successfully');
            }

            // Delete temp file
            Storage::disk($this->tempDisk)->delete($tempUpload->storage_path);

            // Mark as used
            $tempUpload->update([
                'is_used' => true,
                'final_path' => $finalPath,
                'used_at' => Carbon::now(),
            ]);

            Log::info('Temporary file finalized to private storage', [
                'temp_id' => $tempId,
                'original_path' => $tempUpload->storage_path,
                'final_path' => $finalPath,
                'user_id' => $tempUpload->user_id,
                'file_exists' => Storage::disk('local')->exists($finalPath),
                'file_size' => Storage::disk('local')->size($finalPath),
            ]);

            // Generate signed URL for the permanent file
            $signedUrlData = $this->signedUrlService->generateTemporarySignedUrl($finalPath);

            return [
                'success' => true,
                'final_path' => $finalPath,
                'signed_url' => $signedUrlData['url'] ?? null,
                'url_expires_at' => $signedUrlData['expires_at'] ?? null,
                'original_name' => $tempUpload->original_name,
            ];
        }

        throw new \Exception('Temporary file not found in storage');
    }

    /**
     * Cleanup expired temporary files
     */
    public function cleanupExpiredUploads(): array
    {
        $expiredUploads = TemporaryUpload::where(function ($query) {
            $query->where('expires_at', '<=', Carbon::now())
                ->orWhere(function ($q) {
                    $q->where('is_used', false)
                        ->where('created_at', '<=', Carbon::now()->subHours(24));
                });
        })->get();

        $deletedCount = 0;
        $failedDeletions = [];

        foreach ($expiredUploads as $upload) {
            try {
                if (Storage::disk($upload->disk)->exists($upload->storage_path)) {
                    Storage::disk($upload->disk)->delete($upload->storage_path);
                }

                $upload->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                $failedDeletions[] = [
                    'temp_id' => $upload->temp_id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to delete expired temporary upload', [
                    'temp_id' => $upload->temp_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Cleaned up expired temporary uploads', [
            'deleted_count' => $deletedCount,
            'failed_count' => count($failedDeletions),
        ]);

        return [
            'deleted' => $deletedCount,
            'failed' => $failedDeletions,
        ];
    }

    /**
     * Generate temporary ID
     */
    private function generateTempId(): string
    {
        return 'tmp_' . Str::random(20) . '_' . time();
    }

    /**
     * Generate safe filename
     */
    private function generateSafeFileName(string $originalName, string $uniqueId): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $nameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);

        $safeName = Str::slug($nameWithoutExtension);
        $safeName = substr($safeName, 0, 100);

        return $safeName . '_' . $uniqueId . '.' . $extension;
    }

    /**
     * Get allowed file types with descriptions
     */
    public function getAllowedTypesDescriptions(): array
    {
        return [
            'image/jpeg' => 'JPEG Image (.jpg, .jpeg)',
            'image/png' => 'PNG Image (.png)',
            'image/gif' => 'GIF Image (.gif)',
            'image/webp' => 'WebP Image (.webp)',
            'application/pdf' => 'PDF Document (.pdf)',
            'application/msword' => 'Word Document (.doc)',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word Document (.docx)',
            'application/vnd.ms-excel' => 'Excel Spreadsheet (.xls)',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel Spreadsheet (.xlsx)',
        ];
    }

    /**
     * Get upload limits
     */
    public function getUploadLimits(): array
    {
        return [
            'allowed_types' => $this->getAllowedTypesDescriptions(),
            'size_limit' => '5MB (enforced by middleware)',
            'max_file_size_mb' => 5,
        ];
    }

    /**
     * Get temporary upload by ID
     */
    public function getTemporaryUpload(string $tempId): ?TemporaryUpload
    {
        return TemporaryUpload::where('temp_id', $tempId)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Check if temporary upload exists and is valid
     */
    public function isValidTemporaryUpload(string $tempId): bool
    {
        return (bool) $this->getTemporaryUpload($tempId);
    }

    /**
     * Delete temporary upload manually
     */
    public function deleteTemporaryUpload(string $tempId): bool
    {
        $upload = TemporaryUpload::where('temp_id', $tempId)->first();

        if (!$upload) {
            return false;
        }

        try {
            if (Storage::disk($upload->disk)->exists($upload->storage_path)) {
                Storage::disk($upload->disk)->delete($upload->storage_path);
            }

            $upload->delete();

            Log::info('Temporary upload deleted manually', [
                'temp_id' => $tempId,
                'user_id' => $upload->user_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete temporary upload', [
                'temp_id' => $tempId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
