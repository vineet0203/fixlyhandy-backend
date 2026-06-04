<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Services\File\FileValidationRules;
use App\Services\File\FileAttachmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeUpdateService
{
    public function __construct(
        private FileAttachmentService $fileAttachmentService
    ) {}

    /**
     * Update an existing employee
     */
    public function update(Employee $employee, array $data, int $updatedBy): Employee
    {
        DB::beginTransaction();

        try {
            Log::info('=== EMPLOYEE UPDATE START ===', [
                'employee_id' => $employee->id,
                'data_keys' => array_keys($data),
                'updated_by' => $updatedBy
            ]);

            $updateData = [];

            // Allowed fields for update
            $allowedFields = [
                'employee_id',
                'first_name',
                'last_name',
                'date_of_birth',
                'gender',
                'email',
                'mobile_number',
                'address',
                'designation',
                'department',
                'reporting_manager_id',
                'role',
                'is_active',
                'profile_photo_temp_id',
                'remove_profile_photo'
            ];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            // Handle profile photo updates
            if (isset($updateData['profile_photo_temp_id']) || isset($updateData['remove_profile_photo'])) {
                $errors = $this->fileAttachmentService->updateFile(
                    model: $employee,
                    data: $updateData,
                    tempIdField: 'profile_photo_temp_id',
                    pathField: 'profile_photo_path',
                    destinationPath: 'employees/profile-photos',
                    allowedMimeTypes: FileValidationRules::getAllowedMimeTypes('images'),
                    maxSizeKb: FileValidationRules::getSizeLimits('images'),
                    customFilename: $this->generateProfilePhotoFilename(
                        $employee->full_name ?: ($updateData['first_name'] ?? $employee->first_name)
                    ),
                    keepOriginalName: false,
                    removeField: 'remove_profile_photo'
                );

                if (!empty($errors)) {
                    throw new \Exception(implode(', ', $errors['profile_photo_temp_id'] ?? []));
                }
            }

            // Remove temporary fields
            unset($updateData['profile_photo_temp_id']);
            unset($updateData['remove_profile_photo']);

            // Add updated_by
            $updateData['updated_by'] = $updatedBy;

            $employee->update($updateData);

            DB::commit();

            $employee->refresh();
            $employee->load(['reportingManager', 'updater']);

            Log::info('Employee updated successfully', [
                'employee_id' => $employee->id,
                'updated_fields' => array_keys($updateData),
                'updated_by' => $updatedBy,
            ]);

            return $employee;
        } catch (\Exception $e) {
            DB::rollBack();

            // Cleanup temporary upload if update failed
            if (isset($data['profile_photo_temp_id'])) {
                $this->fileAttachmentService->cleanupUnusedTemporaryUpload($data['profile_photo_temp_id']);
            }

            Log::error('Failed to update employee', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'updated_by' => $updatedBy,
            ]);
            throw $e;
        }
    }

    /**
     * Update employee status
     */
    public function updateStatus(Employee $employee, bool $isActive, int $updatedBy): Employee
    {
        return $this->update($employee, ['is_active' => $isActive], $updatedBy);
    }

    /**
     * Update reporting manager
     */
    public function updateReportingManager(Employee $employee, ?int $managerId, int $updatedBy): Employee
    {
        return $this->update($employee, ['reporting_manager_id' => $managerId], $updatedBy);
    }

    /**
     * Generate profile photo filename
     */
    private function generateProfilePhotoFilename(string $employeeName): string
    {
        $slug = \Illuminate\Support\Str::slug($employeeName);
        $timestamp = time();
        $random = \Illuminate\Support\Str::random(6);
        return "profile_{$slug}_{$random}_{$timestamp}.jpg";
    }
}