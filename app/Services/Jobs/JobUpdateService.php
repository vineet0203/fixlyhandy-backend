<?php
// app/Services/Jobs/UpdateService.php

namespace App\Services\Jobs;

use App\Models\Job;
use App\Models\JobActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobUpdateService
{
    /**
     * Update work order
     */
    public function update(Job $Job, array $data, int $updatedBy): Job
    {
        DB::beginTransaction();

        try {
            $oldValues = $Job->toArray();

            // Calculate balance due if total or paid amount changed
            if (isset($data['total_amount']) || isset($data['paid_amount'])) {
                $total = $data['total_amount'] ?? $Job->total_amount;
                $paid = $data['paid_amount'] ?? $Job->paid_amount;
                $data['balance_due'] = $total - $paid;
            }

            // Update work order
            $Job->update(array_merge($data, ['updated_by' => $updatedBy]));

            // Update tasks if provided
            if (!empty($data['tasks'])) {
                $this->updateTasks($Job, $data['tasks'], $updatedBy);
            }

            // Log changes
            $this->logChanges($Job, $oldValues, $Job->toArray(), $updatedBy);

            DB::commit();

            Log::info('Work order updated successfully', [
                'job_id' => $Job->id,
                'updated_by' => $updatedBy,
            ]);

            return $Job->fresh(['client', 'tasks']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update work order', [
                'job_id' => $Job->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update work order status
     */
    public function updateStatus(Job $Job, string $status, int $updatedBy): Job
    {
        $oldStatus = $Job->status;

        if ($status === 'completed') {
            $data = [
                'status' => $status,
                'actual_completion_date' => now(),
            ];
        } else {
            $data = ['status' => $status];
        }

        $Job->update(array_merge($data, ['updated_by' => $updatedBy]));

        // Log status change
        $Job->activities()->create([
            'type' => 'status_change',
            'subject' => 'Status Updated',
            'content' => "Status changed from {$oldStatus} to {$status}",
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => $status,
            ],
            'performed_by' => $updatedBy,
        ]);

        Log::info('Work order status updated', [
            'job_id' => $Job->id,
            'old_status' => $oldStatus,
            'new_status' => $status,
            'updated_by' => $updatedBy,
        ]);

        return $Job;
    }

    /**
     * Update tasks (create, update, delete)
     */
    private function updateTasks(Job $Job, array $tasks, int $updatedBy): void
    {
        $existingTaskIds = $Job->tasks->pluck('id')->toArray();
        $updatedTaskIds = [];
        $sortOrder = 0;

        foreach ($tasks as $taskData) {
            if (isset($taskData['_delete']) && $taskData['_delete'] === true) {
                if (isset($taskData['id'])) {
                    $Job->tasks()->where('id', $taskData['id'])->delete();
                }
                continue;
            }

            if (isset($taskData['id'])) {
                // Update existing task
                $task = $Job->tasks()->find($taskData['id']);
                if ($task) {
                    $task->update([
                        'name' => $taskData['name'],
                        'description' => $taskData['description'] ?? null,
                        'completed' => $taskData['completed'] ?? $task->completed,
                        'due_date' => $taskData['due_date'] ?? null,
                        'sort_order' => $sortOrder++,
                        'updated_by' => $updatedBy,
                    ]);
                    $updatedTaskIds[] = $task->id;
                }
            } else {
                // Create new task
                $task = $Job->tasks()->create([
                    'name' => $taskData['name'],
                    'description' => $taskData['description'] ?? null,
                    'due_date' => $taskData['due_date'] ?? null,
                    'sort_order' => $sortOrder++,
                    'created_by' => $updatedBy,
                    'updated_by' => $updatedBy,
                ]);
                $updatedTaskIds[] = $task->id;
            }
        }

        // Delete tasks that weren't included
        $tasksToDelete = array_diff($existingTaskIds, $updatedTaskIds);
        if (!empty($tasksToDelete)) {
            $Job->tasks()->whereIn('id', $tasksToDelete)->delete();
        }
    }

    /**
     * Log changes for timeline
     */
    private function logChanges(Job $Job, array $oldValues, array $newValues, int $performedBy): void
    {
        $changes = [];

        $trackedFields = [
            'title' => 'Title',
            'status' => 'Status',
            'priority' => 'Priority',
            'work_type' => 'Work Type',
            'assigned_to' => 'Assigned To',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'total_amount' => 'Total Amount',
        ];

        foreach ($trackedFields as $field => $label) {
            if (isset($oldValues[$field]) && isset($newValues[$field]) && $oldValues[$field] != $newValues[$field]) {
                $changes[] = "{$label} changed from '{$oldValues[$field]}' to '{$newValues[$field]}'";
            }
        }

        if (!empty($changes)) {
            $Job->timeline()->create([
                'event_type' => 'updated',
                'description' => implode(', ', $changes),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'performed_by' => $performedBy,
            ]);
        }
    }
}