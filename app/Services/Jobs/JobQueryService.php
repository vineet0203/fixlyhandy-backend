<?php
// app/Services/Jobs/JobQueryService.php

namespace App\Services\Jobs;

use App\Models\Job;
use Illuminate\Pagination\LengthAwarePaginator;

class JobQueryService
{
    /**
     * Get work orders with filtering and pagination
     */
    public function getJobs(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        // Add 'attachments' to the with array
        $query = Job::with([
            'client',
            'tasks',
            'attachments',
            'activities',
            'assignedTo',
            'assignments.employee',
            'createdBy',
            'updatedBy',
            'invoiceItem'
        ]);

        // Apply vendor_id filter (from authenticated user)
        if (!empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('job_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('business_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['status'])) {
            $statuses = explode(',', $filters['status']);
            $query->whereIn('status', $statuses);
        }

        if (!empty($filters['priority'])) {
            $priorities = explode(',', $filters['priority']);
            $query->whereIn('priority', $priorities);
        }

        if (!empty($filters['work_type'])) {
            $types = explode(',', $filters['work_type']);
            $query->whereIn('work_type', $types);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('start_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('end_date', '<=', $filters['date_to']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get single work order by ID
     */
    public function getJob(int $id, array $with = ['client', 'quote', 'tasks', 'activities', 'assignedTo', 'assignments.employee', 'createdBy', 'updatedBy'])
    {
        $vendorId = auth()->user()->vendor_id;

        $query = Job::where('vendor_id', $vendorId);

        if (!empty($with)) {
            $query->with($with);
        }

        // Always load attachments with their context
        $query->with(['attachments' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return $query->find($id);
    }

    /**
     * Get work order by work order number
     */
    public function getJobByNumber(string $jobNumber, array $with = ['client', 'tasks', 'attachments', 'activities', 'assignedTo', 'assignments.employee', 'createdBy', 'updatedBy'])
    {
        $vendorId = auth()->user()->vendor_id;

        $query = Job::where('job_number', $jobNumber)
            ->where('vendor_id', $vendorId);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->first();
    }

    /**
     * Get work order statistics
     */
    public function getJobStatistics(): array
    {
        $vendorId = auth()->user()->vendor_id;

        if (!$vendorId) {
            return [
                'total' => 0,
                'by_status' => [],
                'by_priority' => [],
                'upcoming' => 0,
                'overdue' => 0,
                'total_revenue' => 0,
                'pending_payment' => 0,
            ];
        }

        return [
            'total' => Job::where('vendor_id', $vendorId)->count(),
            'by_status' => [
                'pending' => Job::where('vendor_id', $vendorId)->where('status', 'pending')->count(),
                'scheduled' => Job::where('vendor_id', $vendorId)->where('status', 'scheduled')->count(),
                'in_progress' => Job::where('vendor_id', $vendorId)->where('status', 'in_progress')->count(),
                'on_hold' => Job::where('vendor_id', $vendorId)->where('status', 'on_hold')->count(),
                'completed' => Job::where('vendor_id', $vendorId)->where('status', 'completed')->count(),
                'cancelled' => Job::where('vendor_id', $vendorId)->where('status', 'cancelled')->count(),
            ],
            'by_priority' => [
                'low' => Job::where('vendor_id', $vendorId)->where('priority', 'low')->count(),
                'medium' => Job::where('vendor_id', $vendorId)->where('priority', 'medium')->count(),
                'high' => Job::where('vendor_id', $vendorId)->where('priority', 'high')->count(),
                'urgent' => Job::where('vendor_id', $vendorId)->where('priority', 'urgent')->count(),
            ],
            'upcoming' => Job::where('vendor_id', $vendorId)
                ->whereIn('status', ['pending', 'scheduled'])
                ->where('start_date', '>=', now()->toDateString())
                ->count(),
            'overdue' => Job::where('vendor_id', $vendorId)
                ->whereIn('status', ['pending', 'scheduled', 'in_progress'])
                ->where('estimated_completion_date', '<', now()->toDateString())
                ->count(),
            'total_revenue' => Job::where('vendor_id', $vendorId)
                ->where('status', 'completed')
                ->sum('total_amount'),
            'pending_payment' => Job::where('vendor_id', $vendorId)
                ->whereIn('status', ['completed', 'in_progress'])
                ->where('balance_due', '>', 0)
                ->sum('balance_due'),
        ];
    }

    /**
     * Get applied filters for response
     */
    public function getAppliedFilters(array $filters): array
    {
        $appliedFilters = [];

        $filterLabels = [
            'search' => 'Search',
            'status' => 'Status',
            'priority' => 'Priority',
            'work_type' => 'Work Type',
            'client_id' => 'Client',
            'assigned_to' => 'Assigned To',
            'date_from' => 'Date From',
            'date_to' => 'Date To',
            'vendor_id' => 'Vendor ID', // Added vendor_id
        ];

        foreach ($filters as $key => $value) {
            if (isset($filterLabels[$key]) && !empty($value)) {
                $appliedFilters[] = [
                    'field' => $key,
                    'label' => $filterLabels[$key],
                    'value' => $value,
                ];
            }
        }

        return $appliedFilters;
    }
}
