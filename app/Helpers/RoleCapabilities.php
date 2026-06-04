<?php

namespace App\Helpers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class RoleCapabilities
{
    /**
     * Check if a role can perform a specific action
     */
    public static function can(string $action, string $roleSlug, ?User $user = null): bool
    {
        // First check basic permissions (all roles can update own password)
        if ($action === 'update_own_password') {
            return true; // All authenticated users can update their own password
        }

        // Check permissions from database
        $permissionSlug = self::convertActionToPermissionSlug($action);

        if (!$permissionSlug) {
            return false;
        }

        // Check if role has this permission in database
        $role = Role::where('slug', $roleSlug)->first();

        if (!$role) {
            return false;
        }

        return $role->permissions()->where('slug', $permissionSlug)->exists();
    }

    /**
     * Convert action name to permission slug
     */
    private static function convertActionToPermissionSlug(string $action): ?string
    {
        $matrix = config('permissions');

        // Check all categories
        $categories = [
            'basic_permissions',
            'platform_admin_permissions',
            'vendor_owner_permissions',
            'employee_permissions',
            'client_permissions'
        ];

        foreach ($categories as $category) {
            if (isset($matrix[$category][$action])) {
                $module = self::getModuleFromCategory($category);
                return $module . '.' . str_replace('_', '.', $action);
            }
        }

        return null;
    }

    /**
     * Get module name from category
     */
    private static function getModuleFromCategory(string $category): string
    {
        $mapping = [
            'basic_permissions' => 'profile',
            'platform_admin_permissions' => 'platform',
            'vendor_owner_permissions' => 'vendor',
            'employee_permissions' => 'employee',
            'client_permissions' => 'client',
        ];

        return $mapping[$category] ?? 'system';
    }

    /**
     * Get all actions a role can perform
     */
    public static function getRoleCapabilities(string $roleSlug, ?User $user = null): array
    {
        $role = Role::where('slug', $roleSlug)->with('permissions')->first();

        if (!$role) {
            return [];
        }

        $capabilities = [];

        foreach ($role->permissions as $permission) {
            $action = self::convertPermissionSlugToAction($permission->slug);
            $category = self::getCategoryFromModule($permission->module);

            $capabilities[$category][] = [
                'action' => $action,
                'description' => $permission->description
            ];
        }

        return $capabilities;
    }

    /**
     * Check multiple actions at once
     */
    public static function canAll(array $actions, string $roleSlug, ?User $user = null): bool
    {
        foreach ($actions as $action) {
            if (!self::can($action, $roleSlug, $user)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check any of the actions
     */
    public static function canAny(array $actions, string $roleSlug, ?User $user = null): bool
    {
        foreach ($actions as $action) {
            if (self::can($action, $roleSlug, $user)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all available actions
     */
    public static function getAllActions(): array
    {
        $permissions = Permission::with('roles')->get();
        $actions = [];

        foreach ($permissions as $permission) {
            $action = self::convertPermissionSlugToAction($permission->slug);
            $category = self::getCategoryFromModule($permission->module);

            $actions[] = [
                'action' => $action,
                'category' => $category,
                'description' => $permission->description,
                'allowed_roles' => $permission->roles->pluck('slug')->toArray(),
            ];
        }

        return $actions;
    }

    /**
     * Sync permissions from config to database
     */
    public static function syncPermissionsFromConfig(): array
    {
        $matrix = config('permissions');
        $results = [
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];

        foreach ($matrix as $category => $actions) {
            foreach ($actions as $action => $allowedRoles) {
                try {
                    $module = self::getModuleFromCategory($category);
                    $slug = $module . '.' . str_replace('_', '.', $action);
                    $description = self::getActionDescription($action);
                    
                    // Determine scope based on category
                    $scope = 'vendor'; // default for vendor-scoped permissions
                    if ($category === 'platform_admin_permissions') {
                        $scope = 'platform';
                    }

                    $permission = Permission::updateOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => ucwords(str_replace('_', ' ', $action)),
                            'module' => $module,
                            'scope' => $scope,
                            'description' => $description,
                            'category' => $category,
                        ]
                    );

                    // Sync roles for this permission
                    $roles = Role::whereIn('slug', $allowedRoles)->get();
                    $permission->roles()->sync($roles->pluck('id'));

                    $results['created']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Failed to sync permission {$action}: " . $e->getMessage();
                }
            }
        }

        return $results;
    }

    private static function convertPermissionSlugToAction(string $permissionSlug): string
    {
        $parts = explode('.', $permissionSlug);
        array_shift($parts); // Remove module
        return implode('_', $parts);
    }

    private static function getCategoryFromModule(string $module): string
    {
        $mapping = [
            'profile' => 'basic_permissions',
            'platform' => 'platform_admin_permissions',
            'vendor' => 'vendor_owner_permissions',
            'employee' => 'employee_permissions',
            'client' => 'client_permissions',
        ];

        return $mapping[$module] ?? 'other_permissions';
    }

    /**
     * Get capabilities summary for a role
     */
    public static function getRoleCapabilitiesSummary(string $roleSlug): array
    {
        $role = Role::where('slug', $roleSlug)->first();

        if (!$role) {
            return [
                'role' => $roleSlug,
                'total_actions' => 0,
                'category_summary' => [],
                'capabilities' => [],
            ];
        }

        $capabilities = self::getRoleCapabilities($roleSlug);
        $summary = [];

        foreach ($capabilities as $category => $actions) {
            $summary[$category] = count($actions);
        }

        return [
            'role' => $roleSlug,
            'role_name' => $role->name,
            'description' => $role->description,
            'total_actions' => array_sum($summary),
            'category_summary' => $summary,
            'capabilities' => $capabilities,
        ];
    }

    /**
     * Get all permissions for a role
     */
    public static function getRolePermissions(string $roleSlug, ?User $user = null): array
    {
        $role = Role::where('slug', $roleSlug)->with('permissions')->first();

        if (!$role) {
            return [];
        }

        return $role->permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
                'module' => $permission->module,
                'description' => $permission->description,
            ];
        })->toArray();
    }

    /**
     * Get action description (updated for service management platform)
     */
    public static function getActionDescription(string $action): string
    {
        $descriptions = [
            // Basic permissions
            'update_own_password' => 'Update own password',
            'update_own_profile' => 'Update own profile',
            'view_own_profile' => 'View own profile',

            // Platform Admin permissions
            'view_all_vendors' => 'View all vendors on platform',
            'create_vendor' => 'Create new vendor account',
            'edit_vendor' => 'Edit vendor details',
            'suspend_vendor' => 'Suspend vendor account',
            'activate_vendor' => 'Activate vendor account',
            'delete_vendor' => 'Delete vendor account',
            'view_all_clients' => 'View all platform clients',
            'create_client' => 'Create client account',
            'edit_client' => 'Edit client details',
            'deactivate_client' => 'Deactivate client account',
            'manage_platform_settings' => 'Manage platform settings',
            'view_platform_reports' => 'View platform reports',
            'manage_system_config' => 'Manage system configuration',
            'manage_platform_users' => 'Manage platform users',
            'view_platform_financials' => 'View platform financial reports',
            'view_all_invoices' => 'View all invoices',
            'assign_roles' => 'Assign roles to users',
            'manage_permissions' => 'Manage permissions',

            // Vendor Owner permissions
            'manage_vendor_profile' => 'Manage vendor profile',
            'update_vendor_settings' => 'Update vendor settings',
            'delete_vendor_account' => 'Delete vendor account',
            'add_employees' => 'Add new employees',
            'view_employees' => 'View all employees',
            'edit_employees' => 'Edit employee details',
            'deactivate_employees' => 'Deactivate employees',
            'assign_employee_roles' => 'Assign roles to employees',
            'set_employee_hourly_rates' => 'Set employee hourly rates',
            'add_clients' => 'Add new clients',
            'view_clients' => 'View all clients',
            'edit_clients' => 'Edit client details',
            'deactivate_clients' => 'Deactivate clients',
            'link_employees_to_clients' => 'Link employees to specific clients',
            'create_jobs' => 'Create jobs/tasks',
            'view_all_jobs' => 'View all jobs',
            'edit_jobs' => 'Edit job details',
            'assign_jobs' => 'Assign jobs to employees',
            'update_job_status' => 'Update job status',
            'delete_jobs' => 'Delete jobs',
            'create_schedules' => 'Create work schedules',
            'view_schedules' => 'View all schedules',
            'edit_schedules' => 'Edit schedules',
            'assign_schedules' => 'Assign schedules to employees',
            'view_time_logs' => 'View time tracking logs',
            'approve_time_sheets' => 'Approve time sheets',
            'edit_time_entries' => 'Edit time entries',
            'create_invoices' => 'Create invoices',
            'view_invoices' => 'View all invoices',
            'edit_invoices' => 'Edit invoice details',
            'send_invoices' => 'Send invoices to clients',
            'record_payments' => 'Record payments from clients',
            'view_financial_reports' => 'View financial reports',
            'view_employee_reports' => 'View employee performance reports',
            'view_client_reports' => 'View client activity reports',
            'view_job_reports' => 'View job completion reports',
            'view_revenue_reports' => 'View revenue reports',

            // Employee permissions
            'view_assigned_jobs' => 'View assigned jobs/tasks',
            'update_job_status' => 'Update job status',
            'add_job_notes' => 'Add notes to jobs',
            'upload_job_documents' => 'Upload documents to jobs',
            'clock_in_out' => 'Clock in and out for work',
            'view_own_time_logs' => 'View own time logs',
            'submit_timesheet' => 'Submit timesheet',
            'edit_own_time_entries' => 'Edit own time entries',
            'view_own_schedule' => 'View own schedule',
            'request_schedule_change' => 'Request schedule changes',
            'view_assigned_clients' => 'View assigned clients',
            'communicate_with_clients' => 'Communicate with clients',
            'view_employee_directory' => 'View employee directory',
            'update_own_availability' => 'Update own availability',
            'use_mobile_app' => 'Use mobile application',
            'update_location' => 'Update location',
            'receive_notifications' => 'Receive notifications',

            // Client permissions
            'request_service' => 'Request services from vendors',
            'view_service_requests' => 'View service requests',
            'cancel_service_request' => 'Cancel service requests',
            'view_assigned_jobs' => 'View assigned jobs',
            'track_job_progress' => 'Track job progress',
            'approve_job_completion' => 'Approve job completion',
            'rate_service' => 'Rate service quality',
            'communicate_with_vendor' => 'Communicate with vendor',
            'upload_documents' => 'Upload documents',
            'view_invoices' => 'View invoices',
            'pay_invoices' => 'Pay invoices',
            'view_payment_history' => 'View payment history',
            'download_receipts' => 'Download receipts',
            'manage_client_profile' => 'Manage client profile',
            'update_service_preferences' => 'Update service preferences',
            'view_service_history' => 'View service history',
            'receive_service_updates' => 'Receive service updates',
            'receive_billing_notifications' => 'Receive billing notifications',
        ];

        return $descriptions[$action] ?? ucwords(str_replace('_', ' ', $action));
    }
}