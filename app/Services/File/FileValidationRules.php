<?php

namespace App\Services\File;

class FileValidationRules
{
    /**
     * Get validation rules for temporary upload ID
     */
    public static function tempId(string $fieldName, array $allowedTypes = [], ?int $maxSizeKb = null): array
    {
        return [
            $fieldName => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($allowedTypes, $maxSizeKb, $fieldName) {
                    if (empty($value)) {
                        return;
                    }

                    // Validate format
                    if (!preg_match('/^tmp_[a-zA-Z0-9_]+$/', $value)) {
                        $fail("Invalid temporary upload ID format for {$fieldName}.");
                        return;
                    }

                    // Check if temp upload exists and is valid
                    $tempUpload = \App\Models\TemporaryUpload::where('temp_id', $value)
                        ->where('is_used', false)
                        ->where('expires_at', '>', now())
                        ->first();

                    if (!$tempUpload) {
                        $fail("Invalid or expired temporary upload for {$fieldName}.");
                        return;
                    }

                    // Validate file type
                    if (!empty($allowedTypes) && !in_array($tempUpload->mime_type, $allowedTypes)) {
                        $allowedTypesStr = implode(', ', $allowedTypes);
                        $fail("File type for {$fieldName} must be one of: {$allowedTypesStr}. Uploaded: {$tempUpload->mime_type}");
                        return;
                    }

                    // Validate file size (convert KB to bytes)
                    if ($maxSizeKb !== null && $tempUpload->size > ($maxSizeKb * 1024)) {
                        $maxSizeMb = round($maxSizeKb / 1024, 2);
                        $actualSizeMb = round($tempUpload->size / (1024 * 1024), 2);
                        $fail("File for {$fieldName} is too large. Maximum: {$maxSizeMb}MB, Actual: {$actualSizeMb}MB");
                        return;
                    }
                }
            ]
        ];
    }

    /**
     * Get allowed mime types for common file categories
     */
    public static function getAllowedMimeTypes(string $category): array
    {
        $types = [
            'images' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
            ],
            'documents' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
            ],
            'archives' => [
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/x-tar',
                'application/gzip',
            ],
            'audio' => [
                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
                'audio/x-m4a',
            ],
            'video' => [
                'video/mp4',
                'video/x-msvideo',
                'video/x-matroska',
                'video/quicktime',
                'video/x-ms-wmv',
            ],
        ];

        return $types[$category] ?? [];
    }

    /**
     * Get file size limits for categories
     */
    public static function getSizeLimits(string $category): ?int
    {
        $limits = [
            'images' => 5120, // 5MB in KB
            'documents' => 5120, // 5MB in KB
            'archives' => 51200, // 50MB
            'audio' => 20480, // 20MB
            'video' => 102400, // 100MB
        ];

        return $limits[$category] ?? null;
    }
}