<?php
// app/Services/Jobs/JobTaskService.php

namespace App\Services\Jobs;

use App\Models\Job;
use App\Models\JobTask;
use Illuminate\Support\Facades\Log;

class JobTaskService
{
    /**
     * Add task to work order
     */
    public function addTask(Job $Job, array $data, int $createdBy): JobTask
    {
        $nextSortOrder = $Job->tasks()->max('sort_order') + 1;

        $task = $Job->tasks()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'sort_order' => $nextSortOrder,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);

        // Log activity
        $Job->activities()->create([
            'type' => 'task_added',
            'subject' => 'Task Added',
            'content' => "Task '{$data['name']}' was added",
            'performed_by' => $createdBy,
        ]);

        Log::info('Task added to work order', [
            'job_id' => $Job->id,
            'task_id' => $task->id,
            'created_by' => $createdBy,
        ]);

        return $task;
    }

    /**
     * Toggle task completion status
     */
    public function toggleTask(Job $Job, int $taskId, int $updatedBy): JobTask
    {
        $task = $Job->tasks()->findOrFail($taskId);

        if ($task->completed) {
            $task->markAsIncomplete();
            $action = 'marked incomplete';
        } else {
            $task->markAsCompleted($updatedBy);
            $action = 'completed';
        }

        // Log activity
        $Job->activities()->create([
            'type' => 'task_updated',
            'subject' => 'Task Updated',
            'content' => "Task '{$task->name}' was {$action}",
            'metadata' => [
                'task_id' => $task->id,
                'completed' => $task->completed,
            ],
            'performed_by' => $updatedBy,
        ]);

        // Check if all tasks are completed and update work order status if needed
        if ($Job->tasks()->where('completed', false)->count() === 0) {
            if ($Job->status === 'in_progress') {
                // Auto-complete work order if all tasks are done
                $Job->update([
                    'status' => 'completed',
                    'actual_completion_date' => now(),
                    'updated_by' => $updatedBy,
                ]);
            }
        }

        Log::info('Task toggled', [
            'job_id' => $Job->id,
            'task_id' => $task->id,
            'completed' => $task->completed,
            'updated_by' => $updatedBy,
        ]);

        return $task->fresh();
    }

    /**
     * Delete task
     */
    public function deleteTask(Job $Job, int $taskId): void
    {
        $task = $Job->tasks()->findOrFail($taskId);
        $taskName = $task->name;

        $task->delete();

        // Log activity
        $Job->activities()->create([
            'type' => 'task_deleted',
            'subject' => 'Task Deleted',
            'content' => "Task '{$taskName}' was deleted",
        ]);

        // Reorder remaining tasks
        $remainingTasks = $Job->tasks()->orderBy('sort_order')->get();
        foreach ($remainingTasks as $index => $task) {
            $task->update(['sort_order' => $index]);
        }

        Log::info('Task deleted from work order', [
            'job_id' => $Job->id,
            'task_id' => $taskId,
        ]);
    }
}