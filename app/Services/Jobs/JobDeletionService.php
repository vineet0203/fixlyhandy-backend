<?php
// app/Services/Jobs/JobDeletionService.php

namespace App\Services\Jobs;

use App\Models\Job;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class JobDeletionService
{
    /**
     * Check if work order can be deleted
     */
    public function canDelete(Job $Job): array
    {
        // Check if work order is completed and has payments
        if ($Job->status === 'completed' && $Job->paid_amount > 0) {
            return [
                'can_delete' => false,
                'message' => 'Cannot delete a completed work order with payments. Consider archiving it instead.'
            ];
        }

        // Check if work order is linked to invoices
        // Add this if you have invoicing system

        return [
            'can_delete' => true,
            'message' => 'Work order can be deleted.'
        ];
    }

    /**
     * Soft delete work order
     */
    public function softDelete(Job $Job, int $deletedBy): void
    {
        // Log the deletion
        $Job->activities()->create([
            'type' => 'deleted',
            'subject' => 'Job Deleted',
            'content' => 'Work order was deleted',
            'performed_by' => $deletedBy,
        ]);

        $Job->delete();

        Log::info('Work order soft deleted', [
            'job_id' => $Job->id,
            'deleted_by' => $deletedBy,
        ]);
    }

    /**
     * Force delete work order (permanent)
     */
    public function forceDelete(Job $Job, int $deletedBy): void
    {
        // Delete associated files first
        foreach ($Job->attachments as $attachment) {
            // Delete file from storage
            if ($attachment->file_path) {
                Storage::disk($attachment->disk)->delete($attachment->file_path);
            }
            $attachment->forceDelete();
        }

        // Delete related records without cascade
        $Job->tasks()->forceDelete();
        $Job->activities()->forceDelete();
        $Job->timeline()->forceDelete();
        $Job->schedules()->forceDelete();
        $Job->assignments()->forceDelete();
        
        if (method_exists($Job, 'timeEntries')) {
            $Job->timeEntries()->forceDelete();
        }
        if (method_exists($Job, 'time_entries')) {
            $Job->time_entries()->forceDelete();
        }

        // Force delete the work order
        $Job->forceDelete();

        Log::info('Work order force deleted', [
            'job_id' => $Job->id,
            'deleted_by' => $deletedBy,
        ]);
    }
}