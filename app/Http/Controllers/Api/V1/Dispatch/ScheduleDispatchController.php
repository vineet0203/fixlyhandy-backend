<?php

namespace App\Http\Controllers\Api\V1\Dispatch;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Api\V1\Dispatch\StoreDispatchScheduleRequest;
use App\Http\Requests\Api\V1\Dispatch\UpdateDispatchScheduleRequest;
use App\Models\Crew;
use App\Models\Schedule;
use App\Services\Dispatch\ScheduleDispatchService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ScheduleDispatchController extends BaseController
{
    public function __construct(private readonly ScheduleDispatchService $dispatchService)
    {
    }

    public function index(): JsonResponse
    {
        $vendorId = $this->resolveVendorId();
        if (!$vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $events = Schedule::query()
            ->where('vendor_id', $vendorId)
            ->with(['job:id,title,work_type', 'dispatchCrew:id,name'])
            ->orderBy('start_datetime')
            ->get()
            ->map(function (Schedule $schedule) {
                $assignedEmployeeId = $schedule->employee_id ?: $schedule->crew_id;

                return [
                    'id' => $schedule->id,
                    'title' => $schedule->title ?: ($schedule->job?->title ?? 'Scheduled Job'),
                    'start' => $schedule->calendar_start,
                    'end' => $schedule->calendar_end,
                    'crew_id' => $schedule->dispatch_crew_id,
                    'employee_id' => $assignedEmployeeId,
                    'assigned_employee_id' => $assignedEmployeeId,
                    'status' => $schedule->status,
                    'backgroundColor' => $this->dispatchService->eventColorForSchedule($schedule),
                    'borderColor' => $this->dispatchService->eventColorForSchedule($schedule),
                ];
            });

        return response()->json($events);
    }

    public function store(StoreDispatchScheduleRequest $request): JsonResponse
    {
        $vendorId = $this->resolveVendorId();
        if (!$vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }
        $validated = $request->validated();

        $startDateTime = $this->dispatchService->combineDateAndTime($validated['schedule_date'], $validated['start_time']);
        $endDateTime = $this->dispatchService->combineDateAndTime($validated['schedule_date'], $validated['end_time']);

        $crewBusy = false;
        if (!empty($validated['crew_id'])) {
            $crewBusy = $this->dispatchService->hasCrewOverlap(
                $vendorId,
                (int) $validated['crew_id'],
                $startDateTime,
                $endDateTime
            );

            if ($crewBusy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected crew is busy for the selected time slot.',
                    'status' => 'busy',
                ], 422);
            }
        }

        $schedule = Schedule::create([
            'vendor_id' => $vendorId,
            'job_id' => $validated['job_id'],
            'crew_id' => $validated['employee_id'] ?? null,
            'dispatch_crew_id' => $validated['crew_id'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'title' => $validated['title'] ?? null,
            'schedule_date' => $validated['schedule_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime,
            'status' => $this->dispatchService->mapDispatchStatus($validated['crew_id'] ?? null, $crewBusy, $validated['status'] ?? null),
            'priority' => $validated['priority'] ?? 'normal',
            'location_lat' => $validated['location_lat'] ?? null,
            'location_lng' => $validated['location_lng'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_multi_day' => false,
            'is_recurring' => false,
            'notify_client' => false,
            'notify_crew' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully.',
            'data' => [
                'id' => $schedule->id,
                'title' => $schedule->title ?: ($schedule->job?->title ?? 'Scheduled Job'),
                'start' => $schedule->calendar_start,
                'end' => $schedule->calendar_end,
                'crew_id' => $schedule->dispatch_crew_id,
                'employee_id' => $schedule->employee_id ?: $schedule->crew_id,
                'assigned_employee_id' => $schedule->employee_id ?: $schedule->crew_id,
                'status' => $schedule->status,
            ],
        ], 201);
    }

    public function update(UpdateDispatchScheduleRequest $request, int $id): JsonResponse
    {
        $vendorId = $this->resolveVendorId();
        if (!$vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }
        $schedule = Schedule::query()->where('vendor_id', $vendorId)->findOrFail($id);

        $payload = array_merge([
            'job_id' => $schedule->job_id,
            'crew_id' => $schedule->dispatch_crew_id,
            'employee_id' => $schedule->employee_id,
            'schedule_date' => optional($schedule->schedule_date)->format('Y-m-d') ?? optional($schedule->start_datetime)->format('Y-m-d'),
            'start_time' => $schedule->start_time ? substr((string) $schedule->start_time, 0, 5) : optional($schedule->start_datetime)->format('H:i'),
            'end_time' => $schedule->end_time ? substr((string) $schedule->end_time, 0, 5) : optional($schedule->end_datetime)->format('H:i'),
            'status' => $schedule->status,
            'priority' => $schedule->priority,
            'title' => $schedule->title,
            'address' => $schedule->address,
            'location_lat' => $schedule->location_lat,
            'location_lng' => $schedule->location_lng,
            'notes' => $schedule->notes,
        ], $request->validated());

        $startDateTime = $this->dispatchService->combineDateAndTime($payload['schedule_date'], $payload['start_time']);
        $endDateTime = $this->dispatchService->combineDateAndTime($payload['schedule_date'], $payload['end_time']);

        if (Carbon::parse($endDateTime)->lessThanOrEqualTo(Carbon::parse($startDateTime))) {
            return response()->json([
                'success' => false,
                'message' => 'End time must be after start time.',
            ], 422);
        }

        $crewBusy = false;
        if (!empty($payload['crew_id'])) {
            $crewBusy = $this->dispatchService->hasCrewOverlap(
                $vendorId,
                (int) $payload['crew_id'],
                $startDateTime,
                $endDateTime,
                $schedule->id
            );

            if ($crewBusy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected crew is busy for the selected time slot.',
                    'status' => 'busy',
                ], 422);
            }
        }

        $schedule->update([
            'job_id' => $payload['job_id'],
            'crew_id' => $payload['employee_id'] ?? null,
            'dispatch_crew_id' => $payload['crew_id'] ?? null,
            'employee_id' => $payload['employee_id'] ?? null,
            'title' => $payload['title'] ?? null,
            'schedule_date' => $payload['schedule_date'],
            'start_time' => $payload['start_time'],
            'end_time' => $payload['end_time'],
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime,
            'status' => $this->dispatchService->mapDispatchStatus($payload['crew_id'] ?? null, $crewBusy, $payload['status'] ?? null),
            'priority' => $payload['priority'] ?? 'normal',
            'location_lat' => $payload['location_lat'] ?? null,
            'location_lng' => $payload['location_lng'] ?? null,
            'address' => $payload['address'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully.',
            'data' => [
                'id' => $schedule->id,
                'title' => $schedule->title ?: ($schedule->job?->title ?? 'Scheduled Job'),
                'start' => $schedule->calendar_start,
                'end' => $schedule->calendar_end,
                'crew_id' => $schedule->dispatch_crew_id,
                'employee_id' => $schedule->employee_id ?: $schedule->crew_id,
                'assigned_employee_id' => $schedule->employee_id ?: $schedule->crew_id,
                'status' => $schedule->status,
            ],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $vendorId = $this->resolveVendorId();
        if (!$vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }
        $schedule = Schedule::query()->where('vendor_id', $vendorId)->findOrFail($id);
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully.',
        ]);
    }

    public function upcoming(): JsonResponse
    {
        $vendorId = $this->resolveVendorId();
        if (!$vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $upcoming = Schedule::query()
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['pending', 'assigned', 'in_progress'])
            ->where('start_datetime', '>=', now())
            ->with(['job:id,title', 'dispatchCrew:id,name'])
            ->orderBy('start_datetime')
            ->limit(10)
            ->get()
            ->map(function (Schedule $schedule) {
                $assignedEmployeeId = $schedule->employee_id ?: $schedule->crew_id;

                return [
                    'id' => $schedule->id,
                    'employee_id' => $assignedEmployeeId,
                    'time' => optional($schedule->start_datetime)->format('h:i A'),
                    'job_title' => $schedule->title ?: ($schedule->job?->title ?? 'Scheduled Job'),
                    'location' => $schedule->address,
                    'status' => $schedule->status,
                    'crew' => $schedule->dispatchCrew?->name,
                ];
            });

        return response()->json($upcoming);
    }

    public function crews(): JsonResponse
    {
        $vendorId = $this->resolveVendorId();
        if (!$vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $crews = Crew::query()
            ->with(['members.employee' => fn ($query) => $query->where('vendor_id', $vendorId)])
            ->orderBy('name')
            ->get()
            ->map(function (Crew $crew) {
                return [
                    'id' => $crew->id,
                    'name' => $crew->name,
                    'description' => $crew->description,
                    'members' => $crew->members
                        ->filter(fn ($member) => $member->employee)
                        ->map(fn ($member) => [
                            'id' => $member->employee->id,
                            'name' => $member->employee->full_name,
                            'avatar' => $member->employee->profile_photo_path,
                            'role' => $member->role,
                        ])
                        ->values(),
                ];
            });

        return response()->json($crews);
    }

    private function resolveVendorId(): ?int
    {
        $user = auth()->user();

        if (!$user || !$user->vendor_id) {
            return null;
        }

        return (int) $user->vendor_id;
    }
}
