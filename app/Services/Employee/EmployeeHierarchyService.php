<?php

namespace App\Services\Employee;

use App\Models\Employee;
use Illuminate\Support\Collection;

class EmployeeHierarchyService
{
    /**
     * Get reporting hierarchy for an employee with optional parameters
     * 
     * @param Employee $employee The employee to get hierarchy for
     * @param int|null $maxDepth Maximum depth of hierarchy to retrieve (null = unlimited)
     * @param bool $directReportsOnly If true, returns only direct reports (no nested hierarchy)
     * @param bool $includeDetails If true, includes user roles and job details
     * @return array
     */
    public function getReportingHierarchy(
        Employee $employee,
        ?int $maxDepth = null,
        bool $directReportsOnly = false,
        bool $includeDetails = true
    ): array {
        return [
            'employee' => $this->formatEmployeeData($employee, $includeDetails),
            'upwards' => $this->getUpwardHierarchy($employee, $includeDetails),
            'downwards' => $directReportsOnly
                ? $this->getDirectReportsOnly($employee, $includeDetails)
                : $this->getDownwardHierarchy($employee, $maxDepth, $includeDetails),
            'summary' => $this->getHierarchySummary($employee),
            'parameters' => [
                'max_depth' => $maxDepth,
                'direct_reports_only' => $directReportsOnly,
                'include_details' => $includeDetails
            ]
        ];
    }

    /**
     * Get upward hierarchy (managers chain)
     */
    private function getUpwardHierarchy(Employee $employee, bool $includeDetails = true): array
    {
        $hierarchy = [];
        $current = $employee;
        $level = 0;

        while ($current->manager && $level < 10) { // Limit to prevent infinite loops
            $level++;
            $current = $current->manager;

            $hierarchy[] = [
                'level' => $level,
                'employee' => $this->formatEmployeeData($current, $includeDetails),
                'relationship' => $level === 1 ? 'Direct Manager' : "Level {$level} Manager"
            ];

            // Prevent infinite loop if circular reference
            if ($current->id === $employee->id) {
                break;
            }
        }

        return $hierarchy;
    }

    /**
     * Get downward hierarchy (team structure)
     */
    private function getDownwardHierarchy(
        Employee $employee,
        ?int $maxDepth = null,
        bool $includeDetails = true
    ): array {
        return $this->getTeamHierarchyRecursive($employee, 0, $maxDepth, $includeDetails);
    }

    /**
     * Get only direct reports (no nested hierarchy)
     */
    private function getDirectReportsOnly(Employee $employee, bool $includeDetails = true): array
    {
        $directReports = [];
        $teamMembers = $employee->teamMembers()
            ->when($includeDetails, function ($query) {
                $query->with(['user.roles', 'jobDetails']);
            })
            ->get();

        foreach ($teamMembers as $teamMember) {
            $directReports[] = [
                'employee' => $this->formatEmployeeData($teamMember, $includeDetails),
                'level' => 1,
                'relationship' => 'Direct Report',
                'sub_reports' => [], // Empty since direct reports only
                'has_sub_reports' => $teamMember->teamMembers()->exists()
            ];
        }

        return $directReports;
    }

    /**
     * Recursively get team hierarchy with depth control
     */
    private function getTeamHierarchyRecursive(
        Employee $manager,
        int $currentLevel = 0,
        ?int $maxDepth = null,
        bool $includeDetails = true
    ): array {
        // Check if we've reached max depth
        if ($maxDepth !== null && $currentLevel >= $maxDepth) {
            return [];
        }

        $hierarchy = [];

        // Get direct reports
        $teamMembers = $manager->teamMembers()
            ->when($includeDetails, function ($query) {
                $query->with(['user.roles', 'jobDetails']);
            })
            ->get();

        foreach ($teamMembers as $teamMember) {
            $employeeData = $this->formatEmployeeData($teamMember, $includeDetails);
            $employeeData['level'] = $currentLevel + 1;
            $employeeData['relationship'] = $currentLevel === 0 ? 'Direct Report' : "Level " . ($currentLevel + 1) . " Report";

            // Get sub-reports recursively if not at max depth
            $subReports = [];
            $hasSubReports = false;

            if ($maxDepth === null || $currentLevel + 1 < $maxDepth) {
                $subReports = $this->getTeamHierarchyRecursive($teamMember, $currentLevel + 1, $maxDepth, $includeDetails);
                $hasSubReports = !empty($subReports);
            } else {
                // We're at max depth, just check if there are more levels
                $hasSubReports = $teamMember->teamMembers()->exists();
            }

            $hierarchy[] = [
                'employee' => $employeeData,
                'sub_reports' => $subReports,
                'has_sub_reports' => $hasSubReports,
                'is_truncated' => $maxDepth !== null && $currentLevel + 1 >= $maxDepth && $teamMember->teamMembers()->exists()
            ];
        }

        return $hierarchy;
    }

    /**
     * Get hierarchy summary
     */
    private function getHierarchySummary(Employee $employee): array
    {
        $totalDirectReports = $employee->teamMembers()->count();
        $totalIndirectReports = $this->countIndirectReports($employee);
        $maxDepth = $this->getMaxHierarchyDepth($employee);

        return [
            'total_direct_reports' => $totalDirectReports,
            'total_indirect_reports' => $totalIndirectReports,
            'total_reports' => $totalDirectReports + $totalIndirectReports,
            'manager_chain_length' => $this->getManagerChainLength($employee),
            'max_hierarchy_depth' => $maxDepth,
            'has_multiple_levels' => $maxDepth > 1
        ];
    }

    /**
     * Count indirect reports recursively
     */
    private function countIndirectReports(Employee $manager, ?int $maxDepth = null, int $currentDepth = 0): int
    {
        // Check max depth limit
        if ($maxDepth !== null && $currentDepth >= $maxDepth) {
            return 0;
        }

        $count = 0;
        $directReports = $manager->teamMembers()->get();

        foreach ($directReports as $report) {
            // Count direct reports of this report
            $directReportCount = $report->teamMembers()->count();
            $count += $directReportCount;

            // Recursively count indirect reports if not at max depth
            if ($maxDepth === null || $currentDepth + 1 < $maxDepth) {
                $count += $this->countIndirectReports($report, $maxDepth, $currentDepth + 1);
            }
        }

        return $count;
    }

    /**
     * Get manager chain length
     */
    private function getManagerChainLength(Employee $employee): int
    {
        $length = 0;
        $current = $employee;

        while ($current->manager) {
            $length++;
            $current = $current->manager;

            // Prevent infinite loop
            if ($length > 20) {
                break;
            }
        }

        return $length;
    }

    /**
     * Get maximum hierarchy depth
     */
    private function getMaxHierarchyDepth(Employee $manager, int $currentDepth = 0): int
    {
        $maxDepth = $currentDepth;
        $teamMembers = $manager->teamMembers()->get();

        foreach ($teamMembers as $teamMember) {
            $depth = $this->getMaxHierarchyDepth($teamMember, $currentDepth + 1);
            $maxDepth = max($maxDepth, $depth);
        }

        return $maxDepth;
    }

    /**
     * Format employee data for hierarchy
     */
    private function formatEmployeeData(Employee $employee, bool $includeDetails = true): array
    {
        if ($includeDetails) {
            $employee->loadMissing(['user.roles', 'jobDetails']);
        }

        $data = [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'name' => $employee->full_name,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->personal_email ?? $employee->user->email ?? null,
            'profile_image' => $employee->profile_image,
            'status' => $employee->status,
            'hire_date' => $employee->hire_date?->format('Y-m-d')
        ];

        if ($includeDetails) {
            $data['job_title'] = $employee->jobDetails?->job_title;
            $data['department'] = $employee->jobDetails?->department;
            $data['user_roles'] = $employee->user?->roles?->pluck('slug')->toArray() ?? [];
            $data['is_active'] = $employee->user?->is_active ?? false;
        }

        return $data;
    }

    /**
     * Get flat team list (for easy display) with depth control
     */
    public function getFlatTeamList(
        Employee $manager,
        ?int $maxDepth = null,
        bool $includeDetails = true
    ): array {
        $team = [];
        $this->flattenTeamHierarchy($manager, $team, 0, $maxDepth, $includeDetails);
        return $team;
    }

    /**
     * Flatten team hierarchy into a list with depth control
     */
    private function flattenTeamHierarchy(
        Employee $manager,
        array &$result,
        int $level = 0,
        ?int $maxDepth = null,
        bool $includeDetails = true
    ): void {
        // Check max depth
        if ($maxDepth !== null && $level >= $maxDepth) {
            return;
        }

        $teamMembers = $manager->teamMembers()
            ->when($includeDetails, function ($query) {
                $query->with(['user.roles', 'jobDetails']);
            })
            ->get();

        foreach ($teamMembers as $member) {
            $employeeData = $this->formatEmployeeData($member, $includeDetails);
            $employeeData['level'] = $level;
            $employeeData['reporting_path'] = $this->getReportingPath($member);
            $employeeData['is_truncated'] = $maxDepth !== null && $level >= $maxDepth - 1 && $member->teamMembers()->exists();

            $result[] = $employeeData;

            // Recursively add sub-reports if not at max depth
            if ($maxDepth === null || $level + 1 < $maxDepth) {
                $this->flattenTeamHierarchy($member, $result, $level + 1, $maxDepth, $includeDetails);
            }
        }
    }

    /**
     * Get organization chart data with depth control
     */
    public function getOrganizationChart(
        Employee $rootEmployee,
        ?int $maxDepth = null,
        bool $includeDetails = true
    ): array {
        return [
            'name' => $rootEmployee->full_name,
            'title' => $includeDetails ? ($rootEmployee->jobDetails?->job_title ?? 'Employee') : 'Employee',
            'department' => $includeDetails ? ($rootEmployee->jobDetails?->department ?? null) : null,
            'children' => $this->buildChartChildren($rootEmployee, 0, $maxDepth, $includeDetails),
            'parameters' => [
                'max_depth' => $maxDepth,
                'include_details' => $includeDetails
            ]
        ];
    }

    /**
     * Build children for organization chart with depth control
     */
    private function buildChartChildren(
        Employee $manager,
        int $currentDepth = 0,
        ?int $maxDepth = null,
        bool $includeDetails = true
    ): array {
        // Check max depth
        if ($maxDepth !== null && $currentDepth >= $maxDepth) {
            return [];
        }

        $children = [];
        $teamMembers = $manager->teamMembers()
            ->when($includeDetails, function ($query) {
                $query->with(['user.roles', 'jobDetails']);
            })
            ->get();

        foreach ($teamMembers as $member) {
            $child = [
                'name' => $member->full_name,
                'title' => $includeDetails ? ($member->jobDetails?->job_title ?? 'Employee') : 'Employee',
                'department' => $includeDetails ? ($member->jobDetails?->department ?? null) : null,
                'children' => []
            ];

            // Add children if not at max depth
            if ($maxDepth === null || $currentDepth + 1 < $maxDepth) {
                $child['children'] = $this->buildChartChildren($member, $currentDepth + 1, $maxDepth, $includeDetails);
                $child['has_more_children'] = !empty($child['children']) || $member->teamMembers()->exists();
                $child['is_truncated'] = $maxDepth !== null && $currentDepth + 1 >= $maxDepth && $member->teamMembers()->exists();
            } else {
                $child['has_more_children'] = $member->teamMembers()->exists();
                $child['is_truncated'] = $child['has_more_children'];
            }

            $children[] = $child;
        }

        return $children;
    }

    /**
     * Get reporting path as string
     */
    private function getReportingPath(Employee $employee): string
    {
        $path = [];
        $current = $employee;

        while ($current->manager) {
            array_unshift($path, $current->manager->full_name);
            $current = $current->manager;

            // Prevent infinite loop
            if (count($path) > 10) {
                break;
            }
        }

        return implode(' → ', $path);
    }

    /**
     * Get simplified hierarchy (just counts) for large organizations
     */
    public function getHierarchyCounts(Employee $employee, ?int $maxDepth = null): array
    {
        return [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name
            ],
            'direct_reports_count' => $employee->teamMembers()->count(),
            'indirect_reports_count' => $this->countIndirectReports($employee, $maxDepth),
            'max_depth' => $maxDepth !== null ? min($this->getMaxHierarchyDepth($employee), $maxDepth) : $this->getMaxHierarchyDepth($employee),
            'note' => $maxDepth ? "Counts limited to {$maxDepth} levels deep" : "Counts for entire hierarchy"
        ];
    }

    /**
     * Get paginated flat hierarchy (for very large teams)
     */
    public function getPaginatedHierarchy(
        Employee $manager,
        int $perPage = 50,
        ?int $page = 1,
        ?int $maxDepth = 3,
        bool $includeDetails = true
    ): array {
        // First get all IDs in the hierarchy up to max depth
        $allEmployeeIds = $this->getHierarchyEmployeeIds($manager, $maxDepth);

        // Paginate the IDs
        $paginatedIds = collect($allEmployeeIds)
            ->slice(($page - 1) * $perPage, $perPage)
            ->values()
            ->toArray();

        // Load the employees with details
        $employees = Employee::whereIn('id', $paginatedIds)
            ->when($includeDetails, function ($query) {
                $query->with(['user.roles', 'jobDetails']);
            })
            ->get()
            ->keyBy('id');

        // Format in original order
        $data = [];
        foreach ($paginatedIds as $id) {
            if (isset($employees[$id])) {
                $data[] = $this->formatEmployeeData($employees[$id], $includeDetails);
            }
        }

        $total = count($allEmployeeIds);
        $lastPage = ceil($total / $perPage);

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => (($page - 1) * $perPage) + 1,
                'to' => min($page * $perPage, $total)
            ],
            'links' => [
                'first' => $page > 1 ? "?page=1&per_page={$perPage}" : null,
                'last' => $page < $lastPage ? "?page={$lastPage}&per_page={$perPage}" : null,
                'prev' => $page > 1 ? "?page=" . ($page - 1) . "&per_page={$perPage}" : null,
                'next' => $page < $lastPage ? "?page=" . ($page + 1) . "&per_page={$perPage}" : null,
            ]
        ];
    }

    /**
     * Get all employee IDs in hierarchy up to max depth
     */
    private function getHierarchyEmployeeIds(Employee $manager, ?int $maxDepth = null, int $currentDepth = 0): array
    {
        $ids = [];

        // Check max depth
        if ($maxDepth !== null && $currentDepth >= $maxDepth) {
            return $ids;
        }

        $directReportIds = $manager->teamMembers()->pluck('id')->toArray();
        $ids = array_merge($ids, $directReportIds);

        foreach ($directReportIds as $reportId) {
            $report = Employee::find($reportId);
            if ($report) {
                $subIds = $this->getHierarchyEmployeeIds($report, $maxDepth, $currentDepth + 1);
                $ids = array_merge($ids, $subIds);
            }
        }

        return array_unique($ids);
    }

    /**
     * Check if employee A manages employee B (directly or indirectly)
     */
    public function isManaging(Employee $manager, Employee $employee): bool
    {
        // Direct manager
        if ($employee->manager_id === $manager->id) {
            return true;
        }

        // Check indirect management
        return $this->isIndirectManager($manager, $employee);
    }

    /**
     * Check indirect management recursively
     */
    private function isIndirectManager(Employee $manager, Employee $employee): bool
    {
        if (!$employee->manager) {
            return false;
        }

        if ($employee->manager_id === $manager->id) {
            return true;
        }

        return $this->isIndirectManager($manager, $employee->manager);
    }
}
