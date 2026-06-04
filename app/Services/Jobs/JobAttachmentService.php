<?php
// app/Services/Jobs/JobAttachmentService.php

namespace App\Services\Jobs;

use App\Models\Job;
use App\Models\JobAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobAttachmentService
{
    /**
     * Add attachment to job
     */

    // In your addAttachment method, after creating the attachment, load the relationship
    public function addAttachment(Job $job, UploadedFile $file, int $uploadedBy, ?string $customFileName = null, string $context = JobAttachment::CONTEXT_GENERAL): JobAttachment
    {
        Log::info('JobAttachmentService: Adding attachment', [
            'job_id' => $job->id,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType()
        ]);

        try {
            // Generate file name
            $originalName = $customFileName ?? $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $safeBaseName = Str::slug($baseName);
            $uniqueFileName = $safeBaseName . '_' . time() . '.' . $extension;

            // Store in 'local' disk under private/jobs/context directory
            $contextPath = $context === JobAttachment::CONTEXT_GENERAL ? 'general' : $context;
            $path = $file->storeAs(
                'private/jobs/' . $job->id . '/' . $contextPath,
                $uniqueFileName,
                'local'
            );

            Log::info('JobAttachmentService: File stored', [
                'path' => $path,
                'disk' => 'local',
                'full_path' => storage_path('app/' . $path)
            ]);

            // Create attachment record with disk = 'local'
            $attachment = $job->attachments()->create([
                'context' => $context,
                'file_name' => $originalName,
                'file_path' => $path,
                'file_type' => $this->getFileType($file->getMimeType()),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'disk' => 'local',
                'metadata' => [
                    'context' => $context,
                    'original_name' => $file->getClientOriginalName(),
                    'extension' => $extension,
                    'slug_name' => $safeBaseName,
                    'is_private' => true
                ],
                'uploaded_by' => $uploadedBy,
            ]);

            // IMPORTANT: Load the uploadedBy relationship
            $attachment->load('uploadedBy');

            // Log activity
            // Log activity with context
            $job->activities()->create([
                'type' => 'attachment_added',
                'subject' => 'Attachment Added',
                'content' => "File '{$originalName}' was uploaded to " . ucfirst($context) . " section",
                'metadata' => ['context' => $context],
                'performed_by' => $uploadedBy,
            ]);

            Log::info('JobAttachmentService: Attachment record created', [
                'attachment_id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'file_path' => $attachment->file_path,
                'disk' => $attachment->disk,
                'uploaded_by_loaded' => $attachment->relationLoaded('uploadedBy')
            ]);

            return $attachment;
        } catch (\Exception $e) {
            Log::error('JobAttachmentService: Failed to add attachment', [
                'job_id' => $job->id,
                'context' => $context,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Delete attachment
     */
    public function deleteAttachment(Job $job, int $attachmentId): void
    {
        $attachment = $job->attachments()->findOrFail($attachmentId);

        // Delete file from storage
        if (Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);

            Log::info('JobAttachmentService: File deleted', [
                'attachment_id' => $attachment->id,
                'path' => $attachment->file_path
            ]);
        }

        $fileName = $attachment->file_name;
        $attachment->delete();

        // Log activity
        $job->activities()->create([
            'type' => 'attachment_deleted',
            'subject' => 'Attachment Deleted',
            'content' => "File '{$fileName}' was deleted",
        ]);

        Log::info('JobAttachmentService: Attachment deleted', [
            'job_id' => $job->id,
            'attachment_id' => $attachmentId,
        ]);
    }

    /**
     * Get file type category
     */
    private function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif ($mimeType === 'application/pdf') {
            return 'pdf';
        } elseif (in_array($mimeType, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ])) {
            return 'document';
        } elseif (in_array($mimeType, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ])) {
            return 'spreadsheet';
        } elseif (in_array($mimeType, [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed'
        ])) {
            return 'archive';
        }

        return 'other';
    }

    // Helper method for adding instruction attachments
    public function addInstructionAttachment(Job $job, UploadedFile $file, int $uploadedBy, ?string $customFileName = null): JobAttachment
    {
        return $this->addAttachment($job, $file, $uploadedBy, $customFileName, JobAttachment::CONTEXT_INSTRUCTIONS);
    }

    // Helper method for adding general attachments
    public function addGeneralAttachment(Job $job, UploadedFile $file, int $uploadedBy, ?string $customFileName = null): JobAttachment
    {
        return $this->addAttachment($job, $file, $uploadedBy, $customFileName, JobAttachment::CONTEXT_GENERAL);
    }
}
