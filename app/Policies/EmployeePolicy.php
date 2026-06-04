<?php

namespace App\Policies;

use App\Helpers\RoleCapabilities;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * 1. Determine whether the user can view any employees.
     *
     * Rules:
     * - Platform admin can view all employees
     * - Company owner can view all employees in their company
     * - Manager can view team members
     * - Employee can view themselves (they'll see only their own data in the list)
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users should be able to access the employee list endpoint
        // The controller will filter results based on their role

        // Platform admin can view all employees
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        // Company owner can view all employees in their company
        if ($user->isCompanyOwner()) {
            return true;
        }

        // Manager can view team members
        if ($user->isManager()) {
            return true;
        }
        // Employee can view themselves (they'll see only their own data in the list)
        if ($user->isEmployee()) {
            return true;
        }

        // Employee can only view themselves
        return false;
    }

    /**
     * 2. Determine whether the user can view the employee.
     * 
     * Rules:
     * - Platform admin can view any employee
     * - Employee can view their own profile
     * - Company owner can view all employees in their company
     * - Manager can view team members
     */
    public function view(User $user, Employee $employee): bool
    {
        // Platform admin can view any employee
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        // Check if user belongs to same company
        if ($employee->company_id !== $user->company_id) {
            return false;
        }

        // Employee can view their own profile
        if ($user->employee && $user->employee->id === $employee->id) {
            return RoleCapabilities::can('view_own_profile', $user->getPrimaryRole(), $user);
        }

        // Company owner can view all employees in their company
        if ($user->isCompanyOwner()) {
            return RoleCapabilities::can('read_all_employees', $user->getPrimaryRole(), $user);
        }

        // Manager can view team members
        if ($user->isManager() && $user->employee) {
            $teamMembers = $user->employee->teamMembers()->where('id', $employee->id)->exists();
            if ($teamMembers) {
                return RoleCapabilities::can('view_team_members', $user->getPrimaryRole(), $user);
            }
        }

        return false;
    }

    /**
     * 3. Determine whether the user can create employees.
     * 
     * Rules:
     * - Platform admin can create employees
     * - Company Owner can create employees
     * - Manager can create employees (only if they have associates)
     */
    public function create(User $user): bool
    {
        // Platform admin can create employees
        if ($user->isPlatformSuperAdmin()) {
            return RoleCapabilities::can('full_crud_employees', $user->getPrimaryRole(), $user);
        }

        // Company Owner can create employees
        if ($user->isCompanyOwner()) {
            return RoleCapabilities::can('create_employee', $user->getPrimaryRole(), $user);
        }

        // Manager can create employees (only if they have associates)
        if ($user->isManager()) {
            return RoleCapabilities::can('create_employee', $user->getPrimaryRole(), $user);
        }

        return false;
    }

    /**
     * 4. Determine whether the user can update an employee.
     *
     * Rules:
     * - Employee can update their own profile
     * - Company owner can update employees in their company
     * - Manager can update their team members
     * - Platform admin can update any employee
     */
    public function update(User $user, Employee $employee): bool
    {
        // Platform admin can update any employee
        if ($user->isPlatformSuperAdmin()) {
            return RoleCapabilities::can('full_crud_employees', $user->getPrimaryRole(), $user);
        }

        // Check if user belongs to same company
        if ($employee->company_id !== $user->company_id) {
            return false;
        }

        // Employee can update their own profile
        if ($user->employee && $user->employee->id === $employee->id) {
            return RoleCapabilities::can('update_own_profile', $user->getPrimaryRole(), $user);
        }

        // Company owner can update employees in their company
        if ($user->isCompanyOwner()) {
            return RoleCapabilities::can('update_employee', $user->getPrimaryRole(), $user);
        }

        // Manager can update team members
        if ($user->isManager() && $user->employee) {
            $teamMembers = $user->employee->teamMembers()->where('id', $employee->id)->exists();
            if ($teamMembers) {
                return RoleCapabilities::can('update_team_members', $user->getPrimaryRole(), $user);
            }
        }
        return false;
    }


    /**
     * 5. Determine whether the user can view team members.
     *
     * Rules:
     * - Manager can view team members
     */
    public function viewTeam(User $user): bool
    {
        // Manager can view team members
        if ($user->isManager()) {
            return RoleCapabilities::can('view_team_members', $user->getPrimaryRole(), $user);
        }
        return false;
    }

    /**
     * 6. Determine whether the user can view team hierarchy.
     * 
     * Rules:
     * - Manager can view team hierarchy
     */
    public function viewTeamHierarchy(User $user): bool
    {
        // Must be manager AND have employee record
        return $user->isManager() && $user->employee !== null;
    }

    /**
     * 7. Determine whether the user can view team organization chart.
     * 
     * Rules:
     * - Manager can view team organization chart
     */
    public function viewTeamChart(User $user): bool
    {
        return $user->isManager() && $user->employee !== null;
    }

    /**
     * 6. Determine whether the user can reset passwords.
     *
     * Rules:
     * - Platform admin can reset any password
     * - Company owner can reset employee's passwords in their company
     * - Manager can reset team members' passwords
     * - Employee can reset their own password
     */
    public function resetPassword(User $user, Employee $employee): bool
    {
        // Platform admin can reset any password
        if ($user->isPlatformSuperAdmin()) {
            return RoleCapabilities::can('full_crud_employees', $user->getPrimaryRole(), $user);
        }

        // Check if user belongs to same company
        if ($employee->company_id !== $user->company_id) {
            return false;
        }

        // User can reset their own password
        if ($employee->user_id === $user->id) {
            return RoleCapabilities::can('update_own_password', $user->getPrimaryRole(), $user);
        }

        // Manager can reset team members' passwords
        if ($user->isManager() && $user->employee) {
            $teamMembers = $user->employee->teamMembers()->where('id', $employee->id)->exists();
            if ($teamMembers) {
                return RoleCapabilities::can('update_team_passwords', $user->getPrimaryRole(), $user);
            }
        }

        // Company owner can reset employees' passwords in their company
        if ($user->isCompanyOwner()) {
            return RoleCapabilities::can('update_employee', $user->getPrimaryRole(), $user);
        }

        return false;
    }

    /**
     * 7. Determine whether the user can apply for time-off.
     *
     * Rules:
     * - Platform admin cannot apply leave
     * - Company owner cannot apply time-off
     * - Employees and managers can apply for time-off
     */
    public function applyTimeOff(User $user): bool
    {
        // Platform admin cannot apply leave
        if ($user->isPlatformSuperAdmin()) {
            return false;
        }

        // Company owner cannot apply time-off
        if ($user->isCompanyOwner()) {
            return false;
        }

        // Employees and managers can apply for time-off
        return RoleCapabilities::can('apply_for_timeoff', $user->getPrimaryRole(), $user);
    }

    /**
     *  Determine whether the user can delete the employee.
     *
     * Rules:
     * - Platform admin can delete any employee
     * - Company owner can delete employees in their company
     * - Cannot delete yourself
     */
    // public function delete(User $user, Employee $employee): bool
    // {
    //     // Platform admin can delete any employee
    //     if ($user->isPlatformSuperAdmin()) {
    //         return RoleCapabilities::can('delete_employees', $user->getPrimaryRole(), $user);
    //     }

    //     // Check if user belongs to same company
    //     if ($employee->company_id !== $user->company_id) {
    //         return false;
    //     }

    //     // Cannot delete yourself
    //     if ($employee->user_id === $user->id) {
    //         return false;
    //     }

    //      // Company owner can delete employees in their company
    //     if ($user->isCompanyOwner()) {
    //         return RoleCapabilities::can('delete_employee', $user->getPrimaryRole(), $user);
    //     }

    //     // Company owner cannot delete employees (read-only)
    //     return false;
    // }
}
