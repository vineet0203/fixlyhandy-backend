<?php

namespace App\Providers;

// use App\Models\Candidate;
// use App\Models\Company;
// use App\Models\Employee;
// use App\Policies\CandidatePolicy;
// use App\Policies\CompanyPolicy;
use App\Policies\EmployeePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Employee::class => EmployeePolicy::class,
        // Candidate::class => CandidatePolicy::class,
        // Company::class => CompanyPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // ========== ONLY KEEP NON-MODEL POLICY METHODS ==========
        // These are needed because they don't follow standard policy pattern
        Gate::define('viewTeam', [EmployeePolicy::class, 'viewTeam']);
        Gate::define('viewTeamHierarchy', [EmployeePolicy::class, 'viewTeamHierarchy']);
        Gate::define('viewTeamChart', [EmployeePolicy::class, 'viewTeamChart']);
        Gate::define('applyTimeOff', [EmployeePolicy::class, 'applyTimeOff']);
    }
}
