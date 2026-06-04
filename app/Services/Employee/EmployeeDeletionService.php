<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Services\File\FileAttachmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeDeletionService
{
    public function __construct(
        private FileAttachmentService $fileAttachmentService
    ) {}

    /**
     * Soft delete an employee
     */
    public function softDelete(Employee $employee, int $deletedBy): void
    {
        DB::beginTransaction();

        try {
            Log::info('=== EMPLOYEE SOFT DELETE START ===', [
                'employee_id' => $employee->id,
                'deleted_by' => $deletedBy
            ]);

            // Check if employee has subordinates
            if ($employee->subordinates()->exists()) {
                throw new \Exception('Cannot delete employee with active subordinates. Please reassign them first.');
            }

            // Free unique keys before soft delete so the same employee_id/email can be reused.
            // The employees table currently has global unique indexes on employee_id and email.
            $suffix = '__deleted_' . now()->timestamp . '_' . $employee->id;

            if (!empty($employee->employee_id)) {
                $employee->employee_id = $employee->employee_id . $suffix;
            }

            if (!empty($employee->email)) {
                $employee->email = 'deleted+' . $employee->id . '+' . now()->timestamp . '@fixlyhandy.local';
            }

            // Update audit fields
            $employee->deleted_by = $deletedBy;
            $employee->updated_by = $deletedBy;
            $employee->save();

            // Soft delete the employee
            $employee->delete();

            DB::commit();

            Log::info('Employee soft deleted successfully', [
                'employee_id' => $employee->id,
                'deleted_by' => $deletedBy,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to soft delete employee', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'deleted_by' => $deletedBy,
            ]);
            throw $e;
        }
    }

    /**
     * Force delete an employee (permanent)
     */
    public function forceDelete(Employee $employee, int $deletedBy): void
    {
        DB::beginTransaction();

        try {
            Log::info('=== EMPLOYEE FORCE DELETE START ===', [
                'employee_id' => $employee->id,
                'deleted_by' => $deletedBy
            ]);

            // Delete profile photo if exists
            if ($employee->profile_photo_path) {
                $this->fileAttachmentService->deleteFile($employee->profile_photo_path);
            }

            // Force delete the employee
            $employee->forceDelete();

            DB::commit();

            Log::info('Employee force deleted successfully', [
                'employee_id' => $employee->id,
                'deleted_by' => $deletedBy,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to force delete employee', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'deleted_by' => $deletedBy,
            ]);
            throw $e;
        }
    }

    /**
     * Restore a soft-deleted employee
     */
    public function restore(Employee $employee, int $restoredBy): void
    {
        DB::beginTransaction();

        try {
            Log::info('=== EMPLOYEE RESTORE START ===', [
                'employee_id' => $employee->id,
                'restored_by' => $restoredBy
            ]);

            $employee->restore();
            $employee->deleted_by = null;
            $employee->updated_by = $restoredBy;
            $employee->save();

            DB::commit();

            Log::info('Employee restored successfully', [
                'employee_id' => $employee->id,
                'restored_by' => $restoredBy,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to restore employee', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'restored_by' => $restoredBy,
            ]);
            throw $e;
        }
    }

    /**
     * Check if employee can be deleted
     */
    public function canDelete(Employee $employee): array
    {
        $reasons = [];

        // Check for subordinates
        if ($employee->subordinates()->exists()) {
            $reasons[] = 'Employee has active subordinates';
        }

        // Add more checks as needed (active jobs, assigned tasks, etc.)

        return [
            'can_delete' => empty($reasons),
            'message' => empty($reasons) ? 'Employee can be deleted' : implode(', ', $reasons)
        ];
    }
}