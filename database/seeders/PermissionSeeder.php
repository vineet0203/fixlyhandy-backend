<?php
// database/seeders/PermissionSeeder.php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    private $permissions = [
        // ========== EMPLOYEE PERMISSIONS ==========
        ['name' => 'Create Employee', 'slug' => 'employees.create', 'module' => 'employees', 'category' => 'employees', 'scope' => 'company', 'description' => 'Create new employees'],
        ['name' => 'View All Employees', 'slug' => 'employees.view.all', 'module' => 'employees', 'category' => 'employees', 'scope' => 'company', 'description' => 'View all employees in company'],
        ['name' => 'View Team Employees', 'slug' => 'employees.view.team', 'module' => 'employees', 'category' => 'employees', 'scope' => 'company', 'description' => 'View team members'],
        ['name' => 'View Employee Details', 'slug' => 'employees.view.details', 'module' => 'employees', 'category' => 'employees', 'scope' => 'company', 'description' => 'View employee personal details'],
        ['name' => 'Edit Employee', 'slug' => 'employees.edit', 'module' => 'employees', 'category' => 'employees', 'scope' => 'company', 'description' => 'Edit employee information'],
        ['name' => 'Deactivate Employee', 'slug' => 'employees.deactivate', 'module' => 'employees', 'category' => 'employees', 'scope' => 'company', 'description' => 'Deactivate employee account'],
        ['name' => 'Delete Employee', 'slug' => 'employees.delete', 'module' => 'employees', 'category' => 'employees', 'scope' => 'company', 'description' => 'Delete employee record'],
        
        // ========== CANDIDATE PERMISSIONS ==========
        ['name' => 'Create Candidate', 'slug' => 'candidates.create', 'module' => 'candidates', 'category' => 'candidates', 'scope' => 'company', 'description' => 'Create new candidate'],
        ['name' => 'View Candidates', 'slug' => 'candidates.view', 'module' => 'candidates', 'category' => 'candidates', 'scope' => 'company', 'description' => 'View candidates list'],
        ['name' => 'View Candidate Details', 'slug' => 'candidates.view.details', 'module' => 'candidates', 'category' => 'candidates', 'scope' => 'company', 'description' => 'View candidate detailed information'],
        ['name' => 'Edit Candidate', 'slug' => 'candidates.edit', 'module' => 'candidates', 'category' => 'candidates', 'scope' => 'company', 'description' => 'Edit candidate information'],
        ['name' => 'Delete Candidate', 'slug' => 'candidates.delete', 'module' => 'candidates', 'category' => 'candidates', 'scope' => 'company', 'description' => 'Delete candidate record'],
        ['name' => 'Schedule Interview', 'slug' => 'candidates.schedule.interview', 'module' => 'candidates', 'category' => 'candidates', 'scope' => 'company', 'description' => 'Schedule interview with candidate'],
        ['name' => 'Assign Candidate', 'slug' => 'candidates.assign', 'module' => 'candidates', 'category' => 'candidates', 'scope' => 'company', 'description' => 'Assign candidate to employee'],
        ['name' => 'Add Candidate Notes', 'slug' => 'candidates.add.notes', 'module' => 'candidates', 'category' => 'candidates', 'scope' => 'company', 'description' => 'Add notes to candidate'],
        
        // ========== JOB POST PERMISSIONS ==========
        ['name' => 'Create Job Post', 'slug' => 'job_posts.create', 'module' => 'job_posts', 'category' => 'job_posts', 'scope' => 'company', 'description' => 'Create new job post'],
        ['name' => 'View Job Posts', 'slug' => 'job_posts.view', 'module' => 'job_posts', 'category' => 'job_posts', 'scope' => 'company', 'description' => 'View job posts'],
        ['name' => 'Edit Job Post', 'slug' => 'job_posts.edit', 'module' => 'job_posts', 'category' => 'job_posts', 'scope' => 'company', 'description' => 'Edit job post'],
        ['name' => 'Delete Job Post', 'slug' => 'job_posts.delete', 'module' => 'job_posts', 'category' => 'job_posts', 'scope' => 'company', 'description' => 'Delete job post'],
        ['name' => 'Publish Job Post', 'slug' => 'job_posts.publish', 'module' => 'job_posts', 'category' => 'job_posts', 'scope' => 'company', 'description' => 'Publish job post'],
        ['name' => 'Close Job Post', 'slug' => 'job_posts.close', 'module' => 'job_posts', 'category' => 'job_posts', 'scope' => 'company', 'description' => 'Close job post'],
        
        // ========== LEAVE PERMISSIONS ==========
        ['name' => 'Request Leave', 'slug' => 'leaves.request', 'module' => 'leaves', 'category' => 'leaves', 'scope' => 'company', 'description' => 'Request time off'],
        ['name' => 'View Leaves', 'slug' => 'leaves.view', 'module' => 'leaves', 'category' => 'leaves', 'scope' => 'company', 'description' => 'View leave requests'],
        ['name' => 'Approve Leave', 'slug' => 'leaves.approve', 'module' => 'leaves', 'category' => 'leaves', 'scope' => 'company', 'description' => 'Approve leave requests'],
        ['name' => 'Reject Leave', 'slug' => 'leaves.reject', 'module' => 'leaves', 'category' => 'leaves', 'scope' => 'company', 'description' => 'Reject leave requests'],
        ['name' => 'View Leave Balances', 'slug' => 'leaves.view.balances', 'module' => 'leaves', 'category' => 'leaves', 'scope' => 'company', 'description' => 'View leave balances'],
        
        // ========== COMPANY PERMISSIONS ==========
        ['name' => 'Manage Company Settings', 'slug' => 'company.settings.manage', 'module' => 'company', 'category' => 'company', 'scope' => 'company', 'description' => 'Manage company settings'],
        ['name' => 'View Company Analytics', 'slug' => 'company.analytics.view', 'module' => 'company', 'category' => 'company', 'scope' => 'company', 'description' => 'View company analytics'],
        ['name' => 'Export Company Data', 'slug' => 'company.data.export', 'module' => 'company', 'category' => 'company', 'scope' => 'company', 'description' => 'Export company data'],
        
        // ========== SELF PERMISSIONS ==========
        ['name' => 'View Own Profile', 'slug' => 'self.profile.view', 'module' => 'self', 'category' => 'self', 'scope' => 'company', 'description' => 'View own profile'],
        ['name' => 'Edit Own Profile', 'slug' => 'self.profile.edit', 'module' => 'self', 'category' => 'self', 'scope' => 'company', 'description' => 'Edit own profile'],
        ['name' => 'Change Password', 'slug' => 'self.password.change', 'module' => 'self', 'category' => 'self', 'scope' => 'company', 'description' => 'Change own password'],
    ];
    
    private $rolePermissions = [
        'platform_super_admin' => ['*'],
        
        'company_owner' => [
            'employees.create', 'employees.view.all', 'employees.view.details', 
            'employees.edit', 'employees.deactivate',
            'candidates.create', 'candidates.view', 'candidates.view.details',
            'candidates.edit', 'candidates.delete', 'candidates.schedule.interview',
            'candidates.assign', 'candidates.add.notes',
            'job_posts.create', 'job_posts.view', 'job_posts.edit',
            'job_posts.delete', 'job_posts.publish', 'job_posts.close',
            'leaves.view', 'leaves.approve', 'leaves.reject', 'leaves.view.balances',
            'company.settings.manage', 'company.analytics.view', 'company.data.export',
            'self.profile.view', 'self.profile.edit', 'self.password.change',
        ],
        
        'hr_manager' => [
            'employees.create', 'employees.view.all', 'employees.view.details',
            'employees.edit',
            'candidates.create', 'candidates.view', 'candidates.view.details',
            'candidates.edit', 'candidates.schedule.interview', 'candidates.assign',
            'candidates.add.notes',
            'job_posts.create', 'job_posts.view', 'job_posts.edit',
            'leaves.view', 'leaves.approve', 'leaves.reject', 'leaves.view.balances',
            'self.profile.view', 'self.profile.edit', 'self.password.change',
        ],
        
        'manager' => [
            'employees.view.team',
            'candidates.create', 'candidates.view', 'candidates.schedule.interview',
            'candidates.assign', 'candidates.add.notes',
            'job_posts.create', 'job_posts.view',
            'leaves.view', 'leaves.approve', 'leaves.reject',
            'self.profile.view', 'self.profile.edit', 'self.password.change',
        ],
        
        'employee' => [
            'self.profile.view', 'self.profile.edit',
            'candidates.create', 'candidates.view', 'candidates.add.notes',
            'job_posts.view',
            'leaves.request', 'leaves.view', 'leaves.view.balances',
            'self.profile.view', 'self.profile.edit', 'self.password.change',
        ],
        
        'interviewer' => [
            'candidates.view', 'candidates.add.notes',
            'self.profile.view', 'self.profile.edit', 'self.password.change',
        ],
        
        'candidate' => [
            'self.profile.view', 'self.profile.edit', 'self.password.change',
        ],
    ];

    public function run(): void
    {
        $this->command->info('🚀 Seeding permissions...');
        
        foreach ($this->permissions as $permissionData) {
            Permission::updateOrCreate(
                ['slug' => $permissionData['slug']],
                $permissionData
            );
        }
        
        foreach ($this->rolePermissions as $roleSlug => $permissionSlugs) {
            $role = Role::where('slug', $roleSlug)->first();
            
            if (!$role) {
                $this->command->warn("Role {$roleSlug} not found!");
                continue;
            }
            
            if ($permissionSlugs[0] === '*') {
                $role->permissions()->sync(Permission::all()->pluck('id'));
            } else {
                $permissionIds = Permission::whereIn('slug', $permissionSlugs)
                    ->get()
                    ->pluck('id');
                $role->permissions()->sync($permissionIds);
            }
            
            $this->command->info("✅ Permissions assigned to {$roleSlug}");
        }
        
        $this->command->info('🎉 Permission seeding completed!');
    }
}