<?php
// app/Http/Resources/Api/V1/Job/JobAttachmentResource.php

namespace App\Http\Resources\Api\V1\Job;

use App\Http\Resources\Api\V1\User\UserResource;
use App\Services\File\SignedUrlService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class JobAttachmentResource extends JsonResource
{
    protected $signedUrlService;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->signedUrlService = app(SignedUrlService::class);
    }

    public function toArray($request)
    {
        Log::info('JobAttachmentResource: Building resource', [
            'attachment_id' => $this->id,
            'file_path' => $this->file_path,
            'disk' => $this->disk,
            'has_uploaded_by' => $this->relationLoaded('uploadedBy')
        ]);

        // Generate signed URL for private file
        $signedUrlData = null;
        if ($this->file_path) {
            if ($this->disk === 'public') {
                $signedUrlData = ['url' => url('storage/' . $this->file_path), 'expires_at' => null];
            } else {
                $signedUrlData = $this->signedUrlService->generateTemporarySignedUrl(
                    $this->file_path,
                    60 // 60 minutes expiration
                );
            }
            
            Log::info('JobAttachmentResource: Generated signed URL', [
                'attachment_id' => $this->id,
                'has_url' => !is_null($signedUrlData),
                'url' => $signedUrlData['url'] ?? null
            ]);
        }

        // Safely handle uploaded_by relationship
        $uploadedBy = null;
        if ($this->relationLoaded('uploadedBy') && $this->uploadedBy) {
            $uploadedBy = new UserResource($this->uploadedBy);
        }

        $data = [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'file_type' => $this->file_type,
            'mime_type' => $this->mime_type,
            'file_size' => (int) $this->file_size,
            'formatted_size' => $this->formatFileSize($this->file_size),
            'extension' => pathinfo($this->file_name, PATHINFO_EXTENSION),
            // 'metadata' => $this->metadata,
            'uploaded_by' => $this->uploaded_by,
            'uploaded_at' => $this->created_at?->format('M d, Y H:i'),
            'created_at_diff' => $this->created_at?->diffForHumans(),
        ];

        // Add signed URL if available
        if ($signedUrlData) {
            $data['url'] = $signedUrlData['url'];
            $data['url_expires_at'] = $signedUrlData['expires_at'];
        } else {
            $data['url'] = null;
            $data['file_missing'] = true;
            
            Log::warning('JobAttachmentResource: Could not generate URL', [
                'attachment_id' => $this->id,
                'path' => $this->file_path,
                'disk' => $this->disk
            ]);
        }

        return $data;
    }

    protected function formatFileSize($bytes): string
    {
        if (!$bytes) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    protected function getFileIcon($fileName): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $icons = [
            'pdf' => 'picture_as_pdf',
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'svg' => 'image',
            'webp' => 'image',
            'doc' => 'description',
            'docx' => 'description',
            'xls' => 'table_chart',
            'xlsx' => 'table_chart',
            'ppt' => 'slideshow',
            'pptx' => 'slideshow',
            'zip' => 'archive',
            'rar' => 'archive',
            '7z' => 'archive',
            'txt' => 'article',
            'csv' => 'table_chart',
            'mp3' => 'audio_file',
            'mp4' => 'video_file',
            'mov' => 'video_file',
            'avi' => 'video_file'
        ];

        return $icons[$extension] ?? 'attach_file';
    }
}