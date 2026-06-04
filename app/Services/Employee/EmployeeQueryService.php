<?php

namespace App\Services\Employee;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeQueryService
{
    /**
     * Get employees with filters and pagination
     */
    public function getEmployees(int $vendorId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::where('vendor_id', $vendorId)
            ->with(['reportingManager', 'creator', 'updater'])
            ->withCount('subordinates');

        // Apply filters
        $query = $this->applyFilters($query, $filters);

        // Apply search
        if (!empty($filters['search'])) {
            $query = $this->applySearch($query, $filters['search']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query = $this->applySorting($query, $sortBy, $sortOrder);

        Log::debug('Executing employee query', [
            'vendor_id' => $vendorId,
            'filters' => $filters,
            'per_page' => $perPage,
        ]);

        return $query->paginate($perPage);
    }

    /**
     * Get a specific employee for a vendor
     */
    public function getEmployee(int $vendorId, int $employeeId): ?Employee
    {
        return Employee::where('vendor_id', $vendorId)
            ->where('id', $employeeId)
            ->with(['reportingManager', 'subordinates', 'creator', 'updater'])
            ->withCount('subordinates')
            ->first();
    }

    /**
     * Get employee by employee_id
     */
    public function getEmployeeByEmployeeId(int $vendorId, string $employeeId): ?Employee
    {
        return Employee::where('vendor_id', $vendorId)
            ->where('employee_id', $employeeId)
            ->first();
    }

    /**
     * Get employee by email
     */
    public function getEmployeeByEmail(int $vendorId, string $email): ?Employee
    {
        return Employee::where('vendor_id', $vendorId)
            ->where('email', $email)
            ->first();
    }

    /**
     * Get employees by department
     */
    public function getEmployeesByDepartment(int $vendorId, string $department): Collection
    {
        return Employee::where('vendor_id', $vendorId)
            ->where('department', $department)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get employees by designation
     */
    public function getEmployeesByDesignation(int $vendorId, string $designation): Collection
    {
        return Employee::where('vendor_id', $vendorId)
            ->where('designation', $designation)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get employees reporting to a manager
     */
    public function getSubordinates(int $vendorId, int $managerId): Collection
    {
        return Employee::where('vendor_id', $vendorId)
            ->where('reporting_manager_id', $managerId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get organization hierarchy for a vendor
     */
    public function getOrganizationHierarchy(int $vendorId): array
    {
        // Get all active employees
        $employees = Employee::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->get();

        // Build hierarchy tree
        $hierarchy = [];
        foreach ($employees as $employee) {
            if (!$employee->reporting_manager_id) {
                $hierarchy[] = $this->buildHierarchyNode($employee, $employees);
            }
        }

        return $hierarchy;
    }

    /**
     * Build hierarchy node recursively
     */
    private function buildHierarchyNode(Employee $employee, Collection $allEmployees): array
    {
        $node = [
            'id' => $employee->id,
            'employee_id' => $employee->employee_id,
            'name' => $employee->full_name,
            'designation' => $employee->designation,
            'department' => $employee->department,
            'subordinates' => []
        ];

        foreach ($allEmployees as $subordinate) {
            if ($subordinate->reporting_manager_id === $employee->id) {
                $node['subordinates'][] = $this->buildHierarchyNode($subordinate, $allEmployees);
            }
        }

        return $node;
    }

    /**
     * Get department statistics
     */
    public function getDepartmentStatistics(int $vendorId): array
    {
        return Employee::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->select('department', DB::raw('count(*) as count'))
            ->groupBy('department')
            ->pluck('count', 'department')
            ->toArray();
    }

    /**
     * Get designation statistics
     */
    public function getDesignationStatistics(int $vendorId): array
    {
        return Employee::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->select('designation', DB::raw('count(*) as count'))
            ->groupBy('designation')
            ->pluck('count', 'designation')
            ->toArray();
    }

    /**
     * Get employee statistics
     */
    public function getEmployeeStatistics(int $vendorId): array
    {
        return [
            'total' => Employee::where('vendor_id', $vendorId)->count(),
            'active' => Employee::where('vendor_id', $vendorId)->where('is_active', true)->count(),
            'inactive' => Employee::where('vendor_id', $vendorId)->where('is_active', false)->count(),
            'by_department' => $this->getDepartmentStatistics($vendorId),
            'by_designation' => $this->getDesignationStatistics($vendorId),
            'by_gender' => Employee::where('vendor_id', $vendorId)
                ->select('gender', DB::raw('count(*) as count'))
                ->groupBy('gender')
                ->pluck('count', 'gender')
                ->toArray(),
        ];
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        // Filter by department
        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        // Filter by designation
        if (!empty($filters['designation'])) {
            $query->where('designation', $filters['designation']);
        }

        // Filter by status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Filter by reporting manager
        if (!empty($filters['reporting_manager_id'])) {
            $query->where('reporting_manager_id', $filters['reporting_manager_id']);
        }

        // Filter by gender
        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        return $query;
    }

    /**
     * Apply search to the query
     */
    private function applySearch(Builder $query, string $searchTerm): Builder
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('first_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('employee_id', 'like', '%' . $searchTerm . '%')
                ->orWhere('email', 'like', '%' . $searchTerm . '%')
                ->orWhere('mobile_number', 'like', '%' . $searchTerm . '%')
                ->orWhere('designation', 'like', '%' . $searchTerm . '%')
                ->orWhere('department', 'like', '%' . $searchTerm . '%');
        });
    }

    /**
     * Apply sorting to the query
     */
    private function applySorting(Builder $query, string $sortBy, string $sortOrder): Builder
    {
        $allowedSortFields = [
            'first_name',
            'last_name',
            'employee_id',
            'email',
            'department',
            'designation',
            'created_at',
            'updated_at'
        ];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query;
    }
}