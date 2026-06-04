<?php

namespace App\Services\User;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\UserSecurityLog;
use App\Services\Auth\PasswordService;
use App\Services\Role\RoleService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use React\Http\Client\Client;

class UserAccountService
{
    public function __construct(
        private RoleService $roleService,
        private PasswordService $passwordService
    ) {}

    /**
     * Create or update user account for a candidate/employee
     * 
     * @param array $data User data
     * @param string $roleType 'candidate', 'employee', 'manager', etc.
     * @param int|null $linkedId Candidate ID or Employee ID to link
     * @param string|null $linkedType 'candidate' or 'employee' (optional if no link)
     * @param array $options Additional options
     * @return array
     */
    public function createOrUpdateUserAccount(
        array $data,
        string $roleType,
        ?int $linkedId = null,
        ?string $linkedType = null, // Make this nullable
        array $options = []
    ): array {
        DB::beginTransaction();

        try {
            $defaultOptions = [
                'send_welcome_email' => true,
                'force_password_reset' => true,
                'is_active' => true,
                'status' => 'active',
                'created_by' => null,
                'updated_by' => null,
                'vendor_id' => null,
            ];

            $options = array_merge($defaultOptions, $options);

            // Step 1: Check if user already exists
            $user = $this->findExistingUser($data['email']);
            $isNewUser = !$user;

            if ($isNewUser) {
                // Create new user
                $user = $this->createNewUser($data, $options);
                Log::info('New user account created', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role_type' => $roleType
                ]);
            } else {
                // Update existing user
                $user = $this->updateExistingUser($user, $data, $options);
                Log::info('Existing user account updated', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role_type' => $roleType
                ]);
            }

            // Step 2: Assign role based on roleType
            $this->assignRole($user, $roleType);

            // Step 3: Link to candidate or employee record (only if both parameters provided)
            if ($linkedId && $linkedType) {
                $this->linkToEntity($user, $linkedId, $linkedType);
            }

            // Step 4: Handle notifications
            if ($options['send_welcome_email']) {
                $this->sendWelcomeNotification($user, $roleType, $isNewUser, $options);
            }

            DB::commit();

            return [
                'success' => true,
                'user' => $user,
                'is_new_user' => $isNewUser,
                'role_assigned' => $roleType,
                'linked_to' => $linkedId && $linkedType ? [
                    'id' => $linkedId,
                    'type' => $linkedType
                ] : null,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create/update user account', [
                'email' => $data['email'] ?? 'unknown',
                'role_type' => $roleType,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Failed to create user account: ' . $e->getMessage());
        }
    }

    /**
     * Find existing user by email
     */
    private function findExistingUser(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Create new user
     */
    private function createNewUser(array $data, array $options): User
    {
        $password = $data['password'] ?? $this->passwordService->generateTemporaryPassword();

        // Validate status if provided
        $status = $options['status'] ?? 'active';
        if (!$this->validateStatus($status)) {
            throw new \Exception("Invalid status in user creation: '{$status}'");
        }
        $userData = [
            'vendor_id' => $options['vendor_id'],
            'email' => $data['email'],
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'password' => Hash::make($password),
            'is_active' => $options['is_active'],
            'status' => $status,
            'created_by' => $options['created_by'],
            'updated_by' => $options['created_by'],
            'createdon' => now(),
            'modifiedon' => now(),
        ];

        $user = User::create($userData);

        // Store generated password for notification
        if (!isset($data['password'])) {
            $user->temp_password = $password;
        }

        return $user;
    }

    /**
     * Update existing user
     */
    private function updateExistingUser(User $user, array $data, array $options): User
    {
        $updateData = [];

        if (isset($data['first_name'])) {
            $updateData['first_name'] = $data['first_name'];
        }

        if (isset($data['last_name'])) {
            $updateData['last_name'] = $data['last_name'];
        }

        if (isset($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        if (isset($options['vendor_id'])) {
            $updateData['vendor_id'] = $options['vendor_id'];
        }

        // Validate and update status if provided
        if (isset($options['status'])) {
            if (!$this->validateStatus($options['status'])) {
                throw new \Exception("Invalid status in user update: '{$options['status']}'");
            }
            $updateData['status'] = $options['status'];
            $updateData['is_active'] = in_array($options['status'], ['active', 'pending']);
        }

        if (!empty($updateData)) {
            $updateData['modifiedon'] = now();
            $user->update($updateData);
        }

        return $user;
    }

    /**
     * Assign role to user
     */
    private function assignRole(User $user, string $roleType): void
    {
        // Map role types to role slugs - UPDATED FOR SERVICE MANAGEMENT
        $roleMap = [
            'vendor_owner' => 'vendor_owner', // Changed from 'vendor'
            'employee' => 'employee',
            'client' => 'client',
            'platform_admin' => 'platform_admin', // Changed from 'platform_super_admin'
        ];

        if (!isset($roleMap[$roleType])) {
            throw new \Exception("Invalid role type: {$roleType}");
        }

        $roleSlug = $roleMap[$roleType];

        // Use RoleService to assign role
        $success = $this->roleService->assignSystemRole($user, $roleSlug);

        if (!$success) {
            throw new \Exception("Failed to assign role: {$roleSlug}");
        }

        Log::info('Role assigned to user via RoleService', [
            'user_id' => $user->id,
            'role_slug' => $roleSlug
        ]);
    }

    /**
     * Link user to client or employee
     */
    private function linkToEntity(User $user, int $entityId, string $entityType): void
    {
        switch ($entityType) {
            case 'client':
                $client = Client::find($entityId);
                if ($client) {
                    $client->update(['user_id' => $user->id]);
                    Log::info('User linked to client', [
                        'user_id' => $user->id,
                        'client_id' => $entityId
                    ]);
                }
                break;

            case 'employee':
                $employee = Employee::find($entityId);
                if ($employee) {
                    $employee->update(['user_id' => $user->id]);
                    Log::info('User linked to employee', [
                        'user_id' => $user->id,
                        'employee_id' => $entityId
                    ]);
                }
                break;

            default:
                throw new \Exception("Invalid entity type: {$entityType}");
        }
    }

    /**
     * Send welcome notification
     */
    private function sendWelcomeNotification(
        User $user,
        string $roleType,
        bool $isNewUser,
        array $options
    ): void {
        // In a real application, you would dispatch a job here
        // For now, we'll just log it

        Log::info('Welcome notification should be sent', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role_type' => $roleType,
            'is_new_user' => $isNewUser,
            'has_temp_password' => isset($user->temp_password),
            'force_password_reset' => $options['force_password_reset']
        ]);

        // You would typically dispatch a job like:
        // SendWelcomeEmailJob::dispatch($user, $roleType, $isNewUser, $options);
    }

    /**
     * Create user account for vendor registration (simplified version)
     * This is specifically for vendor registration
     */
    public function createVendorUser(
        array $data,
        int $vendorId,
        ?int $createdBy = null
    ): array {
        return $this->createOrUpdateUserAccount(
            $data,
            'vendor_owner', 
            null, // No linked entity
            null, // No linked type
            [
                'vendor_id' => $vendorId,
                'created_by' => $createdBy,
                'send_welcome_email' => false,
                'force_password_reset' => false,
                'is_active' => true,
            ]
        );
    }


    /**
     * Create user account for existing client (when client already exists)
     */
    public function createUserForExistingCandidate(
        client $client,
        array $userData = [],
        int $createdBy
    ): array {
        return $this->createOrUpdateUserAccount([
            'email' => $client->email,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'password' => $userData['password'] ?? null,
        ], 'candidate', $client->id, 'client', [
            'vendor_id' => $client->vendor_id,
            'created_by' => $createdBy,
            'send_welcome_email' => true,
        ]);
    }

    /**
     * Create user account for existing employee
     */
    public function createUserForExistingEmployee(
        Employee $employee,
        array $userData = [],
        int $createdBy
    ): array {
        return $this->createOrUpdateUserAccount([
            'email' => $employee->personal_email,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'password' => $userData['password'] ?? null,
        ], 'employee', $employee->id, 'employee', [
            'vendor_id' => $employee->vendor_id,
            'created_by' => $createdBy,
            'send_welcome_email' => true,
        ]);
    }

    /**
     * Update user role (e.g., promote employee to manager)
     */
    public function updateUserRole(int $userId, string $newRoleType): array
    {
        DB::beginTransaction();

        try {
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('User not found');
            }

            $this->assignRole($user, $newRoleType);

            DB::commit();

            return [
                'success' => true,
                'user' => $user,
                'new_role' => $newRoleType
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reset user password
     */
    public function resetUserPassword(string $email, bool $sendEmail = true): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception('User not found');
        }

        $newPassword = $this->passwordService->generateTemporaryPassword($user);
        $user->update([
            'password' => Hash::make($newPassword),
            'modifiedon' => now(),
        ]);

        // Store temp password for email
        $user->temp_password = $newPassword;

        if ($sendEmail) {
            $this->sendPasswordResetNotification($user);
        }

        return [
            'success' => true,
            'user' => $user,
            'new_password' => $sendEmail ? null : $newPassword, // Don't expose password if email sent
        ];
    }


    /**
     * Activate user account with audit logging
     */
    public function activateUser(
        int $userId,
        int $activatedBy,
        ?string $reason = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        DB::beginTransaction();

        try {
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Get old status from is_active
            $oldStatus = $user->status;
            $oldIsActive = $user->is_active;

            // Update user - only update is_active since there's no status column
            $user->update([
                'is_active' => true,
                'status' => 'active',
                'reactivated_at' => now(),
                'reactivation_reason' => $reason,
                'deactivation_reason' => null,
                'updated_by' => $activatedBy,
                'modifiedon' => now(),
            ]);

            // Log to audit_logs
            $this->logUserStatusChange(
                $user,
                $oldStatus,
                'active',
                $reason,
                $activatedBy,
                $ipAddress,
                $userAgent
            );

            DB::commit();

            Log::info('User account activated with audit log', [
                'user_id' => $userId,
                'activated_by' => $activatedBy,
                'old_status' => $oldStatus,
                'old_is_active' => $oldIsActive,
                'reason' => $reason,
                'ip_address' => $ipAddress,
            ]);

            return [
                'success' => true,
                'user' => $user->fresh(),
                'action' => 'activated',
                'audit_logged' => true,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to activate user account', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to activate user account: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate user account with audit logging
     */
    public function deactivateUser(
        int $userId,
        int $deactivatedBy,
        ?string $reason = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        DB::beginTransaction();

        try {
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Get old status from is_active
            $oldStatus = $user->status;
            $oldIsActive = $user->is_active;

            // Update user - only update is_active since there's no status column
            $user->update([
                'is_active' => false,
                'status' => 'inactive',
                'deactivated_at' => now(),
                'deactivation_reason' => $reason,
                'reactivation_reason' => null,
                'updated_by' => $deactivatedBy,
                'modifiedon' => now(),
            ]);

            // Log to audit_logs
            $this->logUserStatusChange(
                $user,
                $oldStatus,
                'inactive',
                $reason,
                $deactivatedBy,
                $ipAddress,
                $userAgent
            );

            DB::commit();

            return [
                'success' => true,
                'user' => $user->fresh(),
                'action' => 'deactivated',
                'audit_logged' => true,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to deactivate user account', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to deactivate user account: ' . $e->getMessage());
        }
    }

    /**
     * Update user status (more flexible than activate/deactivate)
     */
    public function updateUserStatus(
        int $userId,
        string $newStatus, // 'active', 'inactive', 'suspended', 'terminated', etc.
        int $changedBy,
        ?string $reason = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        if (!$this->validateStatus($newStatus)) {
            $validStatuses = array_keys($this->getStatusOptions());
            throw new \Exception("Invalid status: '{$newStatus}'. Valid statuses: " . implode(', ', $validStatuses));
        }

        DB::beginTransaction();

        try {
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('User not found');
            }

            if (!$this->validateStatus($user->status)) {
                // Log::warning('User has invalid current status', [
                //     'user_id' => $userId,
                //     'current_status' => $user->status,
                // ]);
                // You might want to set a default or handle this differently
                throw new \Exception('User has invalid current status');
            }

            $oldStatus = $user->status;

            // Determine is_active based on status
            $isActive = in_array($newStatus, ['active', 'pending']); // Pending users might be active

            $updateData = [
                'status' => $newStatus,
                'is_active' => $isActive,
                'updated_by' => $changedBy,
                'modifiedon' => now(),
            ];

            // Set appropriate timestamps
            if ($newStatus === 'inactive' && $oldStatus !== 'inactive') {
                $updateData['deactivated_at'] = now();
                $updateData['deactivation_reason'] = $reason;
                $updateData['reactivated_at'] = null;
                $updateData['reactivation_reason'] = null;
            } elseif ($newStatus === 'active' && $oldStatus !== 'active') {
                $updateData['reactivated_at'] = now();
                $updateData['reactivation_reason'] = $reason;
                $updateData['deactivated_at'] = null;
                $updateData['deactivation_reason'] = null;
            } elseif ($newStatus === 'suspended') {
                $updateData['deactivated_at'] = now();
                $updateData['deactivation_reason'] = $reason ?: 'Account suspended';
            }

            $user->update($updateData);

            // Log to audit_logs
            $this->logUserStatusChange(
                $user,
                $oldStatus,
                $newStatus,
                $reason,
                $changedBy,
                $ipAddress,
                $userAgent
            );

            DB::commit();

            return [
                'success' => true,
                'user' => $user->fresh(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'audit_logged' => true,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * Log user status change to audit_logs
     */
    private function logUserStatusChange(
        User $user,
        string $oldStatus,
        string $newStatus,
        ?string $reason,
        int $changedBy,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {

        // Prepare data for audit log
        $oldValues = [
            'is_active' => (bool)$user->is_active,
            'status' => $oldStatus,
            'deactivated_at' => $user->deactivated_at,
            'reactivated_at' => $user->reactivated_at,
        ];

        $newValues = [
            'is_active' => $newStatus === 'active',
            'status' => $newStatus,
        ];

        // Set timestamps based on action
        if ($newStatus === 'inactive') {
            $newValues['deactivated_at'] = now();
            $newValues['reactivated_at'] = null;

            // Ensure deactivation_reason is set
            if ($reason) {
                $newValues['deactivation_reason'] = $reason;
            }
        } else {
            $newValues['reactivated_at'] = now();
            $newValues['deactivated_at'] = null;

            // Ensure reactivation_reason is set
            if ($reason) {
                $newValues['reactivation_reason'] = $reason;
            }
        }

        // Log to audit_logs table
        AuditLog::create([
            'user_id' => $changedBy, // Who performed the action
            'actor_type' => 'user',
            'event' => 'user_' . ($newStatus === 'active' ? 'activated' : 'deactivated'),
            'entity_type' => 'users',
            'entity_id' => $user->id, // Which user was affected
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($newValues),
            'context' => $reason ?? 'User status changed',
            'meta' => json_encode([
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'changed_by' => $changedBy,
                'reason' => $reason,
            ]),
            'ip_address' => $ipAddress,
            'vendor_id' => $user->vendor_id,
            'created_at' => now(),
        ]);

        // Also log security event (for compliance)
        if (class_exists(UserSecurityLog::class)) {
            UserSecurityLog::create([
                'user_id' => $user->id,
                'event_type' => $newStatus === 'active' ? 'account_unlocked' : 'account_locked',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'metadata' => json_encode([
                    'changed_by' => $changedBy,
                    'reason' => $reason,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]),
                'created_by' => $changedBy,
                'updated_by' => $changedBy,
            ]);
        }
    }

    /**
     * Validate user status
     */
    private function validateStatus(string $status): bool
    {
        $validStatuses = ['active', 'inactive', 'suspended', 'terminated', 'pending'];
        return in_array($status, $validStatuses);
    }

    /**
     * Get available status options
     */
    public function getStatusOptions(): array
    {
        return [
            'active' => ['label' => 'Active', 'description' => 'User can login and use the system'],
            'inactive' => ['label' => 'Inactive', 'description' => 'User cannot login, account is disabled'],
            'suspended' => ['label' => 'Suspended', 'description' => 'Temporary suspension, usually for policy violations'],
            'terminated' => ['label' => 'Terminated', 'description' => 'Permanently terminated, usually for employment ending'],
            'pending' => ['label' => 'Pending', 'description' => 'Awaiting activation or verification'],
        ];
    }

    /**
     * Public method to validate status (can be used by controllers, etc.)
     */
    public function isValidStatus(string $status): bool
    {
        return $this->validateStatus($status);
    }

    /**
     * Get validation error message for status
     */
    public function getStatusValidationMessage(string $status): string
    {
        if ($this->validateStatus($status)) {
            return "Status '{$status}' is valid.";
        }

        $validStatuses = array_keys($this->getStatusOptions());
        return "Invalid status: '{$status}'. Must be one of: " . implode(', ', $validStatuses);
    }


    /**
     * Send password reset notification
     */
    private function sendPasswordResetNotification(User $user): void
    {
        Log::info('Password reset notification should be sent', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        // Dispatch password reset email job
        // SendPasswordResetEmailJob::dispatch($user);
    }
}
