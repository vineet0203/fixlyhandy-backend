<?php
namespace Database\Seeders;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixlyhandyPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Seeding Fixlyhandy permissions...');

        $permissions = [
            // VENDOR
            ['name' => 'Manage Vendor Profile', 'slug' => 'manage_vendor_profile', 'module' => 'vendor', 'category' => 'vendor', 'scope' => 'vendor', 'description' => 'Manage vendor profile'],
            ['name' => 'Update Vendor Settings', 'slug' => 'update_vendor_settings', 'module' => 'vendor', 'category' => 'vendor', 'scope' => 'vendor', 'description' => 'Update vendor settings'],
            // EMPLOYEES
            ['name' => 'Add Employees', 'slug' => 'add_employees', 'module' => 'employees', 'category' => 'employees', 'scope' => 'vendor', 'description' => 'Add employees'],
            ['name' => 'View Employees', 'slug' => 'view_employees', 'module' => 'employees', 'category' => 'employees', 'scope' => 'vendor', 'description' => 'View employees'],
            ['name' => 'Edit Employees', 'slug' => 'edit_employees', 'module' => 'employees', 'category' => 'employees', 'scope' => 'vendor', 'description' => 'Edit employees'],
            // CLIENTS
            ['name' => 'Add Clients', 'slug' => 'add_clients', 'module' => 'clients', 'category' => 'clients', 'scope' => 'vendor', 'description' => 'Add clients'],
            ['name' => 'View Clients', 'slug' => 'view_clients', 'module' => 'clients', 'category' => 'clients', 'scope' => 'vendor', 'description' => 'View clients'],
            // JOBS
            ['name' => 'Create Jobs', 'slug' => 'create_jobs', 'module' => 'jobs', 'category' => 'jobs', 'scope' => 'vendor', 'description' => 'Create jobs'],
            ['name' => 'View Jobs', 'slug' => 'view_jobs', 'module' => 'jobs', 'category' => 'jobs', 'scope' => 'vendor', 'description' => 'View jobs'],
            ['name' => 'Update Job Status', 'slug' => 'update_job_status', 'module' => 'jobs', 'category' => 'jobs', 'scope' => 'vendor', 'description' => 'Update job status'],
            ['name' => 'View Assigned Jobs', 'slug' => 'view_assigned_jobs', 'module' => 'jobs', 'category' => 'jobs', 'scope' => 'vendor', 'description' => 'View assigned jobs'],
            // SCHEDULES
            ['name' => 'Create Schedules', 'slug' => 'create_schedules', 'module' => 'schedules', 'category' => 'schedules', 'scope' => 'vendor', 'description' => 'Create schedules'],
            ['name' => 'View Own Schedule', 'slug' => 'view_own_schedule', 'module' => 'schedules', 'category' => 'schedules', 'scope' => 'vendor', 'description' => 'View own schedule'],
            // INVOICES
            ['name' => 'Create Invoices', 'slug' => 'create_invoices', 'module' => 'invoices', 'category' => 'invoices', 'scope' => 'vendor', 'description' => 'Create invoices'],
            ['name' => 'View Invoices', 'slug' => 'view_invoices', 'module' => 'invoices', 'category' => 'invoices', 'scope' => 'vendor', 'description' => 'View invoices'],
            ['name' => 'Pay Invoices', 'slug' => 'pay_invoices', 'module' => 'invoices', 'category' => 'invoices', 'scope' => 'vendor', 'description' => 'Pay invoices'],
            // REPORTS
            ['name' => 'View Financial Reports', 'slug' => 'view_financial_reports', 'module' => 'reports', 'category' => 'reports', 'scope' => 'vendor', 'description' => 'View financial reports'],
            // PROFILE
            ['name' => 'View Own Profile', 'slug' => 'view_own_profile', 'module' => 'profile', 'category' => 'profile', 'scope' => 'vendor', 'description' => 'View own profile'],
            ['name' => 'Update Own Profile', 'slug' => 'update_own_profile', 'module' => 'profile', 'category' => 'profile', 'scope' => 'vendor', 'description' => 'Update own profile'],
            // TIME
            ['name' => 'Clock In Out', 'slug' => 'clock_in_out', 'module' => 'time', 'category' => 'time', 'scope' => 'vendor', 'description' => 'Clock in/out'],
            ['name' => 'Submit Timesheet', 'slug' => 'submit_timesheet', 'module' => 'time', 'category' => 'time', 'scope' => 'vendor', 'description' => 'Submit timesheet'],
            // CLIENT PORTAL
            ['name' => 'Request Service', 'slug' => 'request_service', 'module' => 'client_portal', 'category' => 'client_portal', 'scope' => 'vendor', 'description' => 'Request service'],
            ['name' => 'Track Job Progress', 'slug' => 'track_job_progress', 'module' => 'client_portal', 'category' => 'client_portal', 'scope' => 'vendor', 'description' => 'Track job progress'],
            ['name' => 'Communicate With Vendor', 'slug' => 'communicate_with_vendor', 'module' => 'client_portal', 'category' => 'client_portal', 'scope' => 'vendor', 'description' => 'Communicate with vendor'],
            ['name' => 'View Service History', 'slug' => 'view_service_history', 'module' => 'client_portal', 'category' => 'client_portal', 'scope' => 'vendor', 'description' => 'View service history'],
            // PLATFORM ADMIN
            ['name' => 'View All Vendors', 'slug' => 'view_all_vendors', 'module' => 'platform', 'category' => 'platform', 'scope' => 'platform', 'description' => 'View all vendors'],
            ['name' => 'Manage Platform Settings', 'slug' => 'manage_platform_settings', 'module' => 'platform', 'category' => 'platform', 'scope' => 'platform', 'description' => 'Manage platform settings'],
            ['name' => 'View Platform Reports', 'slug' => 'view_platform_reports', 'module' => 'platform', 'category' => 'platform', 'scope' => 'platform', 'description' => 'View platform reports'],
            ['name' => 'Manage System Config', 'slug' => 'manage_system_config', 'module' => 'platform', 'category' => 'platform', 'scope' => 'platform', 'description' => 'Manage system config'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['slug' => $p['slug']], $p);
        }

        $rolePermissions = [
            'vendor_owner' => [
                'manage_vendor_profile', 'update_vendor_settings',
                'add_employees', 'view_employees', 'edit_employees',
                'add_clients', 'view_clients',
                'create_jobs', 'view_jobs', 'update_job_status',
                'create_schedules', 'view_own_schedule',
                'create_invoices', 'view_invoices',
                'view_financial_reports',
                'view_own_profile', 'update_own_profile',
            ],
            'employee' => [
                'view_assigned_jobs', 'update_job_status',
                'view_own_schedule', 'clock_in_out', 'submit_timesheet',
                'view_own_profile', 'update_own_profile',
            ],
            'client' => [
                'request_service', 'view_assigned_jobs', 'track_job_progress',
                'view_invoices', 'pay_invoices',
                'communicate_with_vendor', 'view_service_history',
                'view_own_profile', 'update_own_profile',
            ],
            'platform_admin' => [
                'view_all_vendors', 'manage_platform_settings',
                'view_platform_reports', 'manage_system_config',
                'view_own_profile', 'update_own_profile',
            ],
        ];

        foreach ($rolePermissions as $roleSlug => $slugs) {
            $role = Role::where('slug', $roleSlug)->first();
            if (!$role) {
                $this->command->warn("Role {$roleSlug} not found!");
                continue;
            }
            $ids = Permission::whereIn('slug', $slugs)->pluck('id');
            $role->permissions()->sync($ids);
            $this->command->info("✅ Permissions assigned to {$roleSlug} (" . count($ids) . " permissions)");
        }

        $this->command->info('🎉 Done!');
    }
}
