<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VendorTimeEntryController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:all,pending,approved,rejected',
            'employee_id' => 'nullable|integer',
            'employee_search' => 'nullable|string|max:120',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $user = auth()->user();
        $vendorId = (int) ($user->vendor_id ?? 0);

        if (!$vendorId) {
            return $this->forbiddenResponse('Authenticated user is not associated with a vendor.');
        }

        $perPage = max(1, min(50, (int) $request->integer('per_page', 10)));

        $query = TimeEntry::query()
            ->with([
                'employee:id,first_name,last_name,email',
                'job:id,title,job_number,vendor_id',
                'approver:id,first_name,last_name,email',
                'rejector:id,first_name,last_name,email',
            ])
            ->whereHas('job', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            })
            ->orderByDesc('check_in');

        if ($request->filled('status') && $request->string('status')->toString() !== 'all') {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->integer('employee_id'));
        }

        if ($request->filled('employee_search')) {
            $employeeSearch = trim($request->string('employee_search')->toString());
            $query->whereHas('employee', function ($q) use ($employeeSearch) {
                $q->where('first_name', 'like', "%{$employeeSearch}%")
                    ->orWhere('last_name', 'like', "%{$employeeSearch}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$employeeSearch}%"])
                    ->orWhere('email', 'like', "%{$employeeSearch}%");
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = Carbon::parse($request->string('date_from')->toString())->startOfDay();
            $query->where('check_in', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = Carbon::parse($request->string('date_to')->toString())->endOfDay();
            $query->where('check_in', '<=', $dateTo);
        }

        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(function (TimeEntry $entry) {
            return [
                'id' => $entry->id,
                'employee_id' => $entry->employee_id,
                'employee_name' => trim(($entry->employee?->first_name ?? '') . ' ' . ($entry->employee?->last_name ?? '')),
                'employee_email' => $entry->employee?->email,
                'job_id' => $entry->job_id,
                'job_name' => $entry->job?->title,
                'job_number' => $entry->job?->job_number,
                'check_in' => $entry->check_in?->toISOString(),
                'check_out' => $entry->check_out?->toISOString(),
                'total_time' => (int) $entry->total_time,
                'status' => $entry->status,
                'approved_by' => $entry->approved_by,
                'approved_by_name' => $entry->approver ? trim(($entry->approver->first_name ?? '') . ' ' . ($entry->approver->last_name ?? '')) : null,
                'approved_at' => $entry->approved_at?->toISOString(),
                'rejected_by' => $entry->rejected_by,
                'rejected_by_name' => $entry->rejector ? trim(($entry->rejector->first_name ?? '') . ' ' . ($entry->rejector->last_name ?? '')) : null,
                'rejected_at' => $entry->rejected_at?->toISOString(),
                'created_at' => $entry->created_at?->toISOString(),
                'updated_at' => $entry->updated_at?->toISOString(),
            ];
        });

        return $this->successResponse([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 'Vendor time entries fetched successfully.');
    }

    public function approve(int $id): JsonResponse
    {
        $entry = $this->findVendorEntry($id);

        if (!$entry) {
            return $this->notFoundResponse('Time entry not found.');
        }

        if ($entry->status !== 'pending') {
            return $this->conflictErrorResponse('Only pending time entries can be approved.');
        }

        $actor = auth()->user();

        $entry->status = 'approved';
        $entry->approved_by = $actor?->id;
        $entry->approved_at = now();
        $entry->rejected_by = null;
        $entry->rejected_at = null;
        $entry->save();

        $actorName = trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? ''));

        return $this->successResponse([
            'id' => $entry->id,
            'status' => $entry->status,
            'approved_by' => $entry->approved_by,
            'approved_by_name' => $actorName,
            'approved_at' => $entry->approved_at?->toISOString(),
        ], 'Time entry approved successfully.');
    }

    public function reject(int $id): JsonResponse
    {
        $entry = $this->findVendorEntry($id);

        if (!$entry) {
            return $this->notFoundResponse('Time entry not found.');
        }

        if ($entry->status !== 'pending') {
            return $this->conflictErrorResponse('Only pending time entries can be rejected.');
        }

        $actor = auth()->user();

        $entry->status = 'rejected';
        $entry->rejected_by = $actor?->id;
        $entry->rejected_at = now();
        $entry->approved_by = null;
        $entry->approved_at = null;
        $entry->save();

        $actorName = trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? ''));

        return $this->successResponse([
            'id' => $entry->id,
            'status' => $entry->status,
            'rejected_by' => $entry->rejected_by,
            'rejected_by_name' => $actorName,
            'rejected_at' => $entry->rejected_at?->toISOString(),
        ], 'Time entry rejected successfully.');
    }

    private function findVendorEntry(int $id): ?TimeEntry
    {
        $user = auth()->user();
        $vendorId = (int) ($user->vendor_id ?? 0);

        if (!$vendorId) {
            return null;
        }

        return TimeEntry::query()
            ->where('id', $id)
            ->whereHas('job', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            })
            ->first();
    }
}
