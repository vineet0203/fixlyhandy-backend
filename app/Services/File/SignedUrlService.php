<?php
// app/Services/File/SignedUrlService.php

namespace App\Services\File;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\TemporaryUpload;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class SignedUrlService
{
    /**
     * Generate a signed URL for any file
     */
    public function generateTemporarySignedUrl(
        string $path,
        int $expirationMinutes = 60
    ): ?array {
        try {
            // Validate path
            if ($this->containsPathTraversal($path)) {
                Log::warning('Path traversal attempt detected', ['path' => $path]);
                return null;
            }

            // Check if this is a temp path or permanent path
            $isTempPath = str_starts_with($path, 'temp/');
            $isPrivatePath = str_starts_with($path, 'private/');

            if ($isTempPath) {
                // This is a temporary file path
                return $this->generateSignedUrlForTempFile($path, $expirationMinutes);
            } else {
                // This is a permanent file path (could be in private/ or other directories)
                return $this->generateSignedUrlForPermanentFile($path, $expirationMinutes);
            }
        } catch (\Exception $e) {
            Log::error('Error generating signed URL for file', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate signed URL for temporary file
     */
    private function generateSignedUrlForTempFile(
        string $path,
        int $expirationMinutes
    ): ?array {
        // Find temporary upload record by storage_path
        $tempUpload = TemporaryUpload::where('storage_path', $path)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$tempUpload) {
            Log::warning('Temporary file not found or expired', ['path' => $path]);
            return null;
        }

        // Generate signed URL
        $signedUrl = $this->generateSignedUrl([
            'type' => 'temporary',
            'path' => $tempUpload->storage_path,
            'filename' => $tempUpload->original_name,
            'temp_id' => $tempUpload->temp_id,
            'disk' => 'local', // All temp files are on local disk
        ], $expirationMinutes);

        if (!$signedUrl) {
            return null;
        }

        Log::info('Signed URL generated for temporary file', [
            'temp_id' => $tempUpload->temp_id,
            'path' => $path,
            'expires_in_minutes' => $expirationMinutes,
        ]);

        return [
            'url' => $signedUrl,
            'expires_at' => now()->addMinutes($expirationMinutes)->toISOString(),
            'filename' => $tempUpload->original_name,
            'size' => $tempUpload->size,
            'mime_type' => $tempUpload->mime_type,
            'temp_id' => $tempUpload->temp_id,
        ];
    }


    /**
     * Generate signed URL for permanent file
     */
    private function generateSignedUrlForPermanentFile(
        string $path,
        int $expirationMinutes
    ): ?array {
        Log::info('=== GENERATE SIGNED URL FOR PERMANENT FILE ===', [
            'path' => $path,
            'is_private' => str_starts_with($path, 'private/'),
        ]);

        // All permanent files are on local disk, just in different directories
        $disk = Storage::disk('local');
        $diskName = 'local';

        // Check if file exists
        if (!$disk->exists($path)) {
            Log::warning('Permanent file not found', [
                'path' => $path,
                'disk' => $diskName,
                'full_path' => $disk->path($path),
                'directory_exists' => $disk->exists(dirname($path)),
                'files_in_directory' => $disk->files(dirname($path)),
            ]);
            return null;
        }

        // Get file info
        $size = $disk->size($path);
        $mimeType = $disk->mimeType($path);
        $filename = basename($path);

        // Generate signed URL for permanent file
        $signedUrl = $this->generateSignedUrl([
            'type' => 'permanent',
            'path' => $path,
            'filename' => $filename,
            'disk' => $diskName, // Always 'local' for private files
        ], $expirationMinutes);

        if (!$signedUrl) {
            return null;
        }

        Log::info('Signed URL generated for permanent (private) file', [
            'path' => $path,
            'disk' => $diskName,
            'expires_in_minutes' => $expirationMinutes,
        ]);

        return [
            'url' => $signedUrl,
            'expires_at' => now()->addMinutes($expirationMinutes)->toISOString(),
            'filename' => $filename,
            'size' => $size,
            'mime_type' => $mimeType,
        ];
    }


    /**
     * Generate signed URL for parameters
     */
    private function generateSignedUrl(array $params, int $expirationMinutes): string
    {
        // Add expiration timestamp
        $params['expires'] = now()->addMinutes($expirationMinutes)->timestamp;

        // Sort parameters alphabetically
        ksort($params);

        Log::info('Generating signed URL with params', $params);

        // Generate signature from the parameters
        $signature = $this->generateSignature($params);

        // Add signature to parameters
        $params['signature'] = $signature;

        // Generate route signature (the one in the URL path)
        $routeSignature = hash('sha256', json_encode($params));

        // Build final URL
        $url = url("/api/v1/files/signed/{$routeSignature}") . '?' . http_build_query($params);

        Log::info('Generated signed URL', [
            'url' => $url,
            'disk_param' => $params['disk'] ?? 'not_set',
        ]);

        return $url;
    }


    /**
     * Generate signature from parameters
     */
    private function generateSignature(array $params): string
    {
        // Sort parameters
        ksort($params);

        // Create a string to sign
        $stringToSign = '';
        foreach ($params as $key => $value) {
            $stringToSign .= $key . '=' . $value . '&';
        }
        $stringToSign = rtrim($stringToSign, '&');

        // Generate HMAC signature
        return hash_hmac('sha256', $stringToSign, config('app.key'));
    }

    /**
     * Serve file from signed URL
     */
    public function serveSignedFile(Request $request): ?\Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $routeSignature = $request->route('signature');
            $requestSignature = $request->query('signature');
            $type = $request->query('type', 'temporary');
            $path = $request->query('path');
            $filename = $request->query('filename', basename($path));
            $tempId = $request->query('temp_id');
            $diskName = $request->query('disk', 'local'); // Always 'local' now
            $expires = $request->query('expires');

            // Validate required parameters
            if (!$path || !$expires || !$requestSignature) {
                Log::warning('Missing parameters in signed URL', $request->all());
                return null;
            }

            // Check if URL has expired
            if (Carbon::createFromTimestamp($expires)->isPast()) {
                Log::warning('Signed URL has expired', [
                    'path' => $path,
                    'expires' => $expires,
                    'current_time' => now()->timestamp,
                ]);
                return null;
            }

            // Validate path
            if ($this->containsPathTraversal($path)) {
                Log::warning('Path traversal attempt detected', ['path' => $path]);
                return null;
            }

            // Get all query parameters except signature for verification
            $allParams = $request->query();
            $verifyParams = $allParams;
            unset($verifyParams['signature']);

            // Sort parameters for consistent verification
            ksort($verifyParams);

            // Recreate the signature to verify
            $expectedSignature = $this->generateSignature($verifyParams);

            // Verify signature
            if (!hash_equals($expectedSignature, $requestSignature)) {
                Log::warning('Invalid signature in signed URL', [
                    'expected' => $expectedSignature,
                    'received' => $requestSignature,
                    'params' => $verifyParams,
                ]);
                return null;
            }

            // Determine disk - always local for private files
            $disk = Storage::disk($diskName);

            if (!$disk->exists($path)) {
                Log::warning('File not found in storage', [
                    'type' => $type,
                    'path' => $path,
                    'disk' => $diskName,
                    'full_path' => $disk->path($path),
                    'directory_exists' => $disk->exists(dirname($path)),
                    'files_in_parent' => $disk->files(dirname($path)),
                ]);
                return null;
            }

            // For temporary files, also validate the temp_id
            if ($type === 'temporary' && $tempId) {
                $tempUpload = TemporaryUpload::where('temp_id', $tempId)
                    ->where('is_used', false)
                    ->where('expires_at', '>', Carbon::now())
                    ->first();

                if (!$tempUpload) {
                    Log::warning('Temporary file not found or expired via signed URL', [
                        'temp_id' => $tempId,
                        'path' => $path,
                    ]);
                    return null;
                }

                // Use the original filename from the database
                $filename = $tempUpload->original_name;
            }

            $mimeType = $disk->mimeType($path);

            // Determine if we should show inline or force download
            $inlineTypes = [
                'image/png',
                'image/jpeg',
                'image/jpg',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'application/pdf',
                'text/plain',
                'text/html',
                'text/css',
                'text/javascript',
                'application/json',
            ];

            $disposition = in_array($mimeType, $inlineTypes) ? 'inline' : 'attachment';

            // Log successful access
            Log::info('File served via signed URL', [
                'type' => $type,
                'path' => $path,
                'filename' => $filename,
                'mime_type' => $mimeType,
                'disk' => $diskName,
            ]);

            return $disk->download($path, $filename, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
                'Content-Length' => $disk->size($path),
                'Cache-Control' => 'private, max-age=3600, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Exception $e) {
            Log::error('Error serving signed file', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }


    /**
     * Check for path traversal attempts
     */
    private function containsPathTraversal(string $path): bool
    {
        return Str::contains($path, ['..', '//', '\\', '%2e%2e', '%252e%252e']);
    }
}
