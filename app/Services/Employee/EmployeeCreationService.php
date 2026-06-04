<?php

namespace App\Services\Employee;

use App\Exceptions\CrossRoleEmailConflictException;
use App\Models\Employee;
use App\Services\File\FileValidationRules;
use App\Services\File\FileAttachmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class EmployeeCreationService
{
    public function __construct(
        private FileAttachmentService $fileAttachmentService
    ) {}

    /**
     * Create a new employee
     */
    public function create(array $data, int $createdBy): Employee
    {
        if (DB::table('customers')->where('email', $data['email'])->exists()) {
            throw new CrossRoleEmailConflictException(
                'This email is already registered as a Customer.',
                'customer'
            );
        }

        DB::beginTransaction();

        try {
            Log::info('=== EMPLOYEE CREATION START ===', [
                'data_keys' => array_keys($data),
                'has_profile_photo' => isset($data['profile_photo_temp_id']),
                'created_by' => $createdBy
            ]);

            // Always generate employee_id on the server to avoid request collisions.
            $data['employee_id'] = $this->generateEmployeeId();
            Log::info('Auto-generated employee_id', ['employee_id' => $data['employee_id']]);

            $createData = [
                'vendor_id' => $data['vendor_id'],
                'employee_id' => $data['employee_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'email' => $data['email'],
                'password' => null,
                'mobile_number' => $data['mobile_number'],
                'address' => $data['address'] ?? null,
                'designation' => $data['designation'],
                'department' => $data['department'],
                'reporting_manager_id' => $data['reporting_manager_id'] ?? null,
                'role' => $data['role'] ?? 'employee',
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
            ];

            // Handle profile photo if provided
            if (isset($data['profile_photo_temp_id'])) {
                Log::info('🖼️ Attempting to attach profile photo', [
                    'temp_id' => $data['profile_photo_temp_id']
                ]);

                $errors = $this->fileAttachmentService->attachFile(
                    data: $createData,
                    tempIdField: 'profile_photo_temp_id',
                    pathField: 'profile_photo_path',
                    destinationPath: 'employees/profile-photos',
                    allowedMimeTypes: FileValidationRules::getAllowedMimeTypes('images'),
                    maxSizeKb: FileValidationRules::getSizeLimits('images'),
                    customFilename: $this->generateProfilePhotoFilename(
                        $data['first_name'] . ' ' . ($data['last_name'] ?? '')
                    ),
                    keepOriginalName: false
                );

                if (!empty($errors)) {
                    throw new \Exception(implode(', ', $errors['profile_photo_temp_id'] ?? []));
                }

                Log::info('✅ Profile photo attached successfully');
            }

            // Remove temporary fields
            unset($createData['profile_photo_temp_id']);

            $employee = null;

            // Protect against race conditions in high concurrency environments.
            for ($attempt = 1; $attempt <= 5; $attempt++) {
                try {
                    $createData['employee_id'] = $this->generateEmployeeId();
                    $employee = Employee::create($createData);
                    break;
                } catch (QueryException $e) {
                    $isDuplicateEmployeeId = str_contains(strtolower($e->getMessage()), 'employee_id')
                        && str_contains(strtolower($e->getMessage()), 'duplicate');

                    if (!$isDuplicateEmployeeId || $attempt === 5) {
                        throw $e;
                    }

                    Log::warning('Duplicate employee_id detected during create, retrying', [
                        'attempt' => $attempt,
                    ]);
                }
            }

            if (!$employee) {
                throw new \RuntimeException('Unable to generate unique employee_id after retries.');
            }

            DB::commit();

            // Load relationships
            $employee->load(['reportingManager', 'creator']);

            Log::info('Employee created successfully', [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_id,
                'vendor_id' => $employee->vendor_id,
                'created_by' => $createdBy,
            ]);

            return $employee;
        } catch (\Exception $e) {
            DB::rollBack();

            // Cleanup temporary upload if creation failed
            if (isset($data['profile_photo_temp_id'])) {
                $this->fileAttachmentService->cleanupUnusedTemporaryUpload($data['profile_photo_temp_id']);
            }

            Log::error('Failed to create employee', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'created_by' => $createdBy,
            ]);
            throw $e;
        }
    }

    /**
     * Generate a unique employee ID
     */
    private function generateEmployeeId(): string
    {
        $prefix = 'EMP' . now()->format('Ym');

        $latestEmployeeId = Employee::withTrashed()
            ->where('employee_id', 'like', $prefix . '%')
            ->orderByDesc('employee_id')
            ->value('employee_id');

        $sequence = 1;
        if ($latestEmployeeId && preg_match('/^' . preg_quote($prefix, '/') . '(\d{4})$/', $latestEmployeeId, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        do {
            $candidate = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $exists = Employee::withTrashed()->where('employee_id', $candidate)->exists();
            $sequence++;
        } while ($exists);

        return $candidate;
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