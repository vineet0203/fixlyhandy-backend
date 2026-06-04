<?php

namespace Database\Seeders;

use App\Helpers\RoleCapabilities;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemDataSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data
        DB::table('role_permissions')->delete();
        DB::table('permissions')->delete();
        DB::table('roles')->delete();
        
        // Platform-level role (platform scope)
        $platformRoles = [
            [
                'name' => 'Platform Administrator',
                'slug' => 'platform_admin',
                'description' => 'Full platform access. Manages all vendors, system settings, and platform-wide configurations.',
                'scope' => 'platform',
                'is_system_role' => true,
            ]
        ];

        // Vendor-level roles (vendor scope)
        $vendorRoles = [
            [
                'name' => 'Vendor Owner',
                'slug' => 'vendor_owner',
                'description' => 'Full access to vendor account. Can manage employees, clients, jobs, schedules, billing, and vendor settings.',
                'scope' => 'vendor',
                'is_system_role' => true,
            ],
            [
                'name' => 'Employee',
                'slug' => 'employee',
                'description' => 'Vendor employee (technician, dispatcher, admin). Access based on assigned permissions within the vendor organization.',
                'scope' => 'vendor',
                'is_system_role' => true,
            ],
            [
                'name' => 'Client',
                'slug' => 'client',
                'description' => 'Service client. Can request services, view job status, communicate with vendors, and manage payments.',
                'scope' => 'vendor', // Client scope is vendor-specific (tied to specific vendors)
                'is_system_role' => true,
            ]
        ];

        // Create all roles
        foreach (array_merge($platformRoles, $vendorRoles) as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }

        // Sync permissions from config
        $this->command->info('Syncing permissions from config to database...');
        $results = RoleCapabilities::syncPermissionsFromConfig();
        
        $this->command->info("✓ Created/Updated {$results['created']} permissions");
        
        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $error) {
                $this->command->error($error);
            }
        }
        
        $this->command->info("\n=== SERVICE MANAGEMENT PLATFORM ROLES ===");
        $this->command->info("Platform Scope (1 role):");
        $this->command->info("  1. Platform Admin: Manages entire platform");
        $this->command->info("\nVendor Scope (3 roles):");
        $this->command->info("  2. Vendor Owner: Service business owner");
        $this->command->info("  3. Employee: Vendor staff (technicians, dispatchers, admins)");
        $this->command->info("  4. Client: Service customers (vendor-specific)");
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Display role capabilities summary
        $this->displayRoleCapabilities();
    }

    private function displayRoleCapabilities()
    {
        $this->command->info("\n=== ROLE CAPABILITIES SUMMARY ===");
        
        $roles = Role::orderBy('scope')->get();
        foreach ($roles as $role) {
            $this->command->info("\n{$role->name} ({$role->slug}):");
            $this->command->info("  Scope: {$role->scope}");
            $this->command->info("  Description: {$role->description}");
            
            // Get permissions for this role
            $permissions = Permission::whereHas('roles', function ($query) use ($role) {
                $query->where('slug', $role->slug);
            })->get();
            
            $this->command->info("  Total Permissions: " . $permissions->count());
            
            // Group by module
            $modules = $permissions->groupBy('module');
            foreach ($modules as $module => $modulePermissions) {
                $this->command->info("  {$module}: " . $modulePermissions->count() . " permissions");
            }
        }
        
        // Show role hierarchy and capabilities
        $this->command->info("\n=== ROLE HIERARCHY & DATA ACCESS ===");
        
        $this->command->info("\n1. PLATFORM ADMINISTRATOR (platform scope):");
        $this->command->info("   - Can view/manage ALL vendors");
        $this->command->info("   - Cannot access vendor-specific data (jobs, clients, employees)");
        $this->command->info("   - Platform-wide settings only");
        
        $this->command->info("\n2. VENDOR OWNER (vendor scope):");
        $this->command->info("   - Full access to OWN vendor data only");
        $this->command->info("   - Cannot see other vendors' data");
        $this->command->info("   - Manages employees, clients, jobs, billing");
        
        $this->command->info("\n3. EMPLOYEE (vendor scope):");
        $this->command->info("   - Access to OWN vendor's data only");
        $this->command->info("   - Permissions limited by role subset (technician, dispatcher, admin)");
        $this->command->info("   - Cannot see other vendors' data");
        
        $this->command->info("\n4. CLIENT (vendor scope):");
        $this->command->info("   - Access to specific vendors they're linked to");
        $this->command->info("   - Cannot see other clients' data");
        $this->command->info("   - Limited to service requests and communication");
        
        $this->command->info("\n=== DATA ISOLATION RULES ===");
        $this->command->info("• Vendor Owner: Sees only their vendor's data");
        $this->command->info("• Employee: Sees only their vendor's data");
        $this->command->info("• Client: Sees only vendors they're connected to");
        $this->command->info("• Platform Admin: Sees all vendors but no vendor-specific data");
    }
}