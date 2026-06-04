<?php

namespace App\Services\Role;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RoleService
{
    /**
     * Assign system role to user
     */
    public function assignSystemRole(User $user, string $roleSlug, ?User $assignedBy = null): bool
    {
        try {
            Log::info("Attempting to assign system role", [
                'user_id' => $user->id,
                'role_slug' => $roleSlug,
                'email' => $user->email
            ]);

            $role = Role::where('slug', $roleSlug)
                ->where('is_system_role', true)
                ->first();

            if (!$role) {
                Log::error("System role not found", [
                    'role_slug' => $roleSlug,
                    'available_roles' => Role::where('is_system_role', true)->pluck('slug')->toArray()
                ]);
                return false;
            }

            // Check if user already has this role
            if ($user->roles()->where('role_id', $role->id)->exists()) {
                Log::info("User already has this role", [
                    'user_id' => $user->id,
                    'role_slug' => $roleSlug
                ]);
                return true;
            }

            $assignedById = config('system.user_id') ?? ($assignedBy ? $assignedBy->id : 1);

            $user->roles()->attach($role->id, [
                'assigned_by' => $assignedById,
                'assigned_at' => now(),
            ]);

            Log::info("✅ System role assigned successfully", [
                'user_id' => $user->id,
                'role_slug' => $roleSlug,
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            // Reload the user roles to ensure they're fresh
            $user->load('roles');

            return true;
        } catch (\Exception $e) {
            Log::error('❌ Failed to assign system role', [
                'user_id' => $user->id,
                'role_slug' => $roleSlug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Assign vendor role to user (vendor-specific roles)
     */
    public function assignVendorRole(User $user, string $roleSlug, ?User $assignedBy = null): bool
    {
        $vendorRoles = ['vendor_owner', 'employee', 'client'];
        
        if (!in_array($roleSlug, $vendorRoles)) {
            Log::error("Invalid vendor role", ['role_slug' => $roleSlug]);
            return false;
        }
        
        return $this->assignSystemRole($user, $roleSlug, $assignedBy);
    }

    /**
     * Get user's roles
     */
    public function getUserRoles(User $user): array
    {
        return $user->roles()->get()->toArray();
    }

    /**
     * Get user's primary role
     */
    public function getUserPrimaryRole(User $user): ?string
    {
        $roles = $user->roles()->orderBy('scope', 'desc')->get();
        
        // Platform admin is highest priority
        foreach ($roles as $role) {
            if ($role->slug === 'platform_admin') {
                return 'platform_admin';
            }
        }
        
        // Then vendor owner
        foreach ($roles as $role) {
            if ($role->slug === 'vendor_owner') {
                return 'vendor_owner';
            }
        }
        
        // Then employee
        foreach ($roles as $role) {
            if ($role->slug === 'employee') {
                return 'employee';
            }
        }
        
        // Then client
        foreach ($roles as $role) {
            if ($role->slug === 'client') {
                return 'client';
            }
        }
        
        return null;
    }

    /**
     * Check if user has a specific role
     */
    public function userHasRole(User $user, string $roleSlug): bool
    {
        return $user->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user has any of the specified roles
     */
    public function userHasAnyRole(User $user, array $roleSlugs): bool
    {
        return $user->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser(User $user, string $roleSlug): bool
    {
        try {
            $role = Role::where('slug', $roleSlug)->first();

            if (!$role) {
                return false;
            }

            $user->roles()->detach($role->id);

            Log::info("Role removed from user", [
                'user_id' => $user->id,
                'role_slug' => $roleSlug
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove role from user', [
                'user_id' => $user->id,
                'role_slug' => $roleSlug,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all system roles
     */
    public function getSystemRoles(): array
    {
        return Role::where('is_system_role', true)->get()->toArray();
    }

    /**
     * Get vendor-specific system roles
     */
    public function getVendorRoles(): array
    {
        return Role::where('is_system_role', true)
            ->where('scope', 'vendor')
            ->get()
            ->toArray();
    }

    /**
     * Get platform-level system roles
     */
    public function getPlatformRoles(): array
    {
        return Role::where('is_system_role', true)
            ->where('scope', 'platform')
            ->get()
            ->toArray();
    }

    /**
     * Ensure system roles exist for service management platform
     */
    public function ensureSystemRolesExist(): void
    {
        $requiredRoles = [
            'platform_admin' => [
                'name' => 'Platform Administrator',
                'scope' => 'platform',
                'description' => 'Full platform access. Manages all vendors, system settings, and platform-wide configurations.'
            ],
            'vendor_owner' => [
                'name' => 'Vendor Owner',
                'scope' => 'vendor',
                'description' => 'Full access to vendor account. Can manage employees, clients, jobs, schedules, billing, and vendor settings.'
            ],
            'employee' => [
                'name' => 'Employee',
                'scope' => 'vendor',
                'description' => 'Vendor employee (technician, dispatcher, admin). Access based on assigned permissions within the vendor organization.'
            ],
            'client' => [
                'name' => 'Client',
                'scope' => 'vendor',
                'description' => 'Service client. Can request services, view job status, communicate with vendors, and manage payments.'
            ]
        ];

        foreach ($requiredRoles as $roleSlug => $roleData) {
            $role = Role::where('slug', $roleSlug)->first();
            
            if (!$role) {
                Log::warning("Required system role missing: {$roleSlug}");
                
                // Create the missing role
                Role::create([
                    'slug' => $roleSlug,
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'scope' => $roleData['scope'],
                    'is_system_role' => true,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);
                
                Log::info("✅ Created missing system role: {$roleSlug}");
            }
        }
    }

    /**
     * Check if user is platform admin
     */
    public function isPlatformAdmin(User $user): bool
    {
        return $this->userHasRole($user, 'platform_admin');
    }

    /**
     * Check if user is vendor owner
     */
    public function isVendorOwner(User $user): bool
    {
        return $this->userHasRole($user, 'vendor_owner');
    }

    /**
     * Check if user is employee
     */
    public function isEmployee(User $user): bool
    {
        return $this->userHasRole($user, 'employee');
    }

    /**
     * Check if user is client
     */
    public function isClient(User $user): bool
    {
        return $this->userHasRole($user, 'client');
    }

    /**
     * Get user's vendor scope (if applicable)
     */
    public function getUserVendorScope(User $user): ?string
    {
        $roles = $user->roles()->where('scope', 'vendor')->get();
        
        if ($roles->isEmpty()) {
            return null;
        }
        
        // Return the highest vendor role
        if ($this->isVendorOwner($user)) {
            return 'vendor_owner';
        }
        
        if ($this->isEmployee($user)) {
            return 'employee';
        }
        
        if ($this->isClient($user)) {
            return 'client';
        }
        
        return null;
    }

    /**
     * Sync user roles (replace all existing roles with new ones)
     */
    public function syncUserRoles(User $user, array $roleSlugs, ?User $assignedBy = null): bool
    {
        try {
            Log::info("Syncing roles for user", [
                'user_id' => $user->id,
                'new_roles' => $roleSlugs
            ]);

            // Get role IDs
            $roles = Role::whereIn('slug', $roleSlugs)->get();
            
            if ($roles->isEmpty()) {
                Log::error("No valid roles found", ['role_slugs' => $roleSlugs]);
                return false;
            }

            $roleIds = $roles->pluck('id')->toArray();
            $assignedById = $assignedBy ? $assignedBy->id : 1;

            // Prepare pivot data
            $pivotData = [];
            foreach ($roleIds as $roleId) {
                $pivotData[$roleId] = [
                    'assigned_by' => $assignedById,
                    'assigned_at' => now(),
                ];
            }

            // Sync roles
            $user->roles()->sync($pivotData);

            Log::info("✅ User roles synced successfully", [
                'user_id' => $user->id,
                'role_count' => count($roleIds)
            ]);

            // Reload the user roles
            $user->load('roles');

            return true;
        } catch (\Exception $e) {
            Log::error('❌ Failed to sync user roles', [
                'user_id' => $user->id,
                'role_slugs' => $roleSlugs,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}