<?php

namespace App\Policies;

use App\Helpers\RoleCapabilities;
use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any companies.
     */
    public function viewAny(User $user): bool
    {
        // Only platform admin can view all companies
        return RoleCapabilities::can('full_crud_companies', $user->getPrimaryRole(), $user);
    }

    /**
     * Determine whether the user can view the company.
     */
    public function view(User $user, Company $company): bool
    {
        // Platform admin can view any company
        if ($user->isPlatformSuperAdmin()) {
            return RoleCapabilities::can('full_crud_companies', $user->getPrimaryRole(), $user);
        }

        // Users can view their own company
        return $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $user, Company $company): bool
    {
        // Platform admin can update any company
        if ($user->isPlatformSuperAdmin()) {
            return RoleCapabilities::can('full_crud_companies', $user->getPrimaryRole(), $user);
        }

        // Check if user belongs to the company
        if ($user->company_id !== $company->id) {
            return false;
        }

        // Only company owner can update company settings
        return $user->isCompanyOwner();
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $user, Company $company): bool
    {
        // Only platform admin can delete companies
        return RoleCapabilities::can('delete_companies', $user->getPrimaryRole(), $user);
    }

    /**
     * Determine whether the user can manage company settings.
     */
    public function manageSettings(User $user, Company $company): bool
    {
        // Platform admin can manage any company settings
        if ($user->isPlatformSuperAdmin()) {
            return RoleCapabilities::can('full_crud_companies', $user->getPrimaryRole(), $user);
        }

        // Check if user belongs to the company
        if ($user->company_id !== $company->id) {
            return false;
        }

        // Only company owner can manage settings
        return $user->isCompanyOwner();
    }

    /**
     * Determine whether the user can view company data.
     */
    public function viewData(User $user, Company $company): bool
    {
        // Platform admin can view any company data
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        // Check if user belongs to the company
        if ($user->company_id !== $company->id) {
            return false;
        }

        // Company owner has read-only access to all company data
        if ($user->isCompanyOwner()) {
            return RoleCapabilities::can('read_company_data', $user->getPrimaryRole(), $user);
        }

        // Employees and managers can view company data (limited by other policies)
        return true;
    }
}