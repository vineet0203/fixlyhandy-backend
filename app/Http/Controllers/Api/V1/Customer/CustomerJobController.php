<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Customer;
use App\Models\Job;
use Illuminate\Http\JsonResponse;

class CustomerJobController extends BaseController
{
    public function index(): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        $jobs = Job::query()
            ->with([
                'quote',
                'client',
                'tasks',
                'attachments'
            ])
            ->where(function ($query) use ($customer) {
                $query->where('customer_id', $customer->id)
                    ->orWhereHas('quote', function ($quoteQuery) use ($customer) {
                        $quoteQuery->where('customer_id', $customer->id)
                            ->orWhere('client_email', $customer->email);
                    });
            })
            ->latest('id')
            ->paginate(15);

        // Map using JobResource and include schedules manually if needed or just return standard
        $data = $jobs->map(function ($job) {
            $resource = (new \App\Http\Resources\Api\V1\Job\JobResource($job))->resolve();
            $resource['schedules'] = $job->schedules ? $job->schedules->map(fn($s) => [
                'id' => $s->id,
                'schedule_date' => $s->schedule_date ? $s->schedule_date->format('Y-m-d') : ($s->start_datetime ? $s->start_datetime->format('Y-m-d') : null),
                'start_time' => $s->start_time ?: ($s->start_datetime ? $s->start_datetime->format('H:i') : null),
                'end_time' => $s->end_time ?: ($s->end_datetime ? $s->end_datetime->format('H:i') : null),
                'location' => $s->address ?: null,
                'status' => $s->status,
            ])->values() : [];
            return $resource;
        });

        return $this->successResponse([
            'data' => $data,
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
        ], 'Customer jobs retrieved successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        $job = Job::query()
            ->with([
                'quote',
                'client',
                'tasks',
                'attachments',
                'schedules',
                'assignedTo',
                'assignments.employee'
            ])
            ->where('id', $id)
            ->where(function ($query) use ($customer) {
                $query->where('customer_id', $customer->id)
                    ->orWhereHas('quote', function ($quoteQuery) use ($customer) {
                        $quoteQuery->where('customer_id', $customer->id)
                            ->orWhere('client_email', $customer->email);
                    });
            })
            ->first();

        if (!$job) {
            return $this->notFoundResponse('Job not found.');
        }

        $resource = (new \App\Http\Resources\Api\V1\Job\JobResource($job))->resolve();
        $resource['schedules'] = $job->schedules ? $job->schedules->map(fn($s) => [
            'id' => $s->id,
            'schedule_date' => $s->schedule_date ? $s->schedule_date->format('Y-m-d') : ($s->start_datetime ? $s->start_datetime->format('Y-m-d') : null),
            'start_time' => $s->start_time ?: ($s->start_datetime ? $s->start_datetime->format('H:i') : null),
            'end_time' => $s->end_time ?: ($s->end_datetime ? $s->end_datetime->format('H:i') : null),
            'location' => $s->address ?: null,
            'status' => $s->status,
        ])->values() : [];
        
        // Ensure readonly flags
        $resource['can_edit'] = false;
        $resource['can_delete'] = false;

        return $this->successResponse($resource, 'Customer job retrieved successfully.');
    }

    private function getAuthenticatedCustomer(): Customer
    {
        $customerData = request()->attributes->get('customer');
        return Customer::findOrFail((int) $customerData['id']);
    }
}
