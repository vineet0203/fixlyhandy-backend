<?php

namespace App\Http\Requests\Api\V1\Schedule;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vendorId = auth()->user()->vendor_id;

        return [
            // Job & Crew
            'job_id' => [
                'sometimes',
                'integer',
                Rule::exists('jobs', 'id')->where(function ($query) use ($vendorId) {
                    $query->where('vendor_id', $vendorId)->whereNull('deleted_at');
                }),
            ],
            'crew_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where(function ($query) use ($vendorId) {
                    $query->where('vendor_id', $vendorId)->where('is_active', true);
                }),
            ],

            // Date & Time
            'start_datetime' => 'sometimes|date',
            'end_datetime' => 'sometimes|date|after:start_datetime',

            // Details
            'priority' => ['sometimes', Rule::in(['normal', 'high', 'emergency'])],
            'status' => ['sometimes', Rule::in(['draft', 'scheduled', 'completed', 'cancelled'])],
            'notes' => 'nullable|string|max:2000',
            'address' => 'nullable|string|max:500',

            // Options
            'is_multi_day' => 'nullable|boolean',
            'is_recurring' => 'nullable|boolean',
            'notify_client' => 'nullable|boolean',
            'notify_crew' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'job_id.exists' => 'The selected job does not exist or has been deleted.',
            'crew_id.exists' => 'The selected crew member does not exist or is inactive.',
            'start_datetime.date' => 'Invalid start date/time format.',
            'end_datetime.date' => 'Invalid end date/time format.',
            'end_datetime.after' => 'End date/time must be after the start date/time.',
            'priority.in' => 'Priority must be normal, high, or emergency.',
            'status.in' => 'Status must be draft, scheduled, completed, or cancelled.',
            'notes.max' => 'Notes cannot exceed 2000 characters.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $this->validateMinimumDuration($validator);
            $this->validateCrewOverlap($validator);
        });
    }

    /**
     * Ensure the schedule is at least 15 minutes long.
     */
    protected function validateMinimumDuration($validator): void
    {
        $scheduleId = $this->route('id') ?? $this->route('schedule');
        $schedule = Schedule::find($scheduleId);
        if (!$schedule) {
            return;
        }

        $start = $this->has('start_datetime')
            ? Carbon::parse($this->input('start_datetime'))
            : $schedule->start_datetime;

        $end = $this->has('end_datetime')
            ? Carbon::parse($this->input('end_datetime'))
            : $schedule->end_datetime;

        if ($end->diffInMinutes($start) < 15) {
            $validator->errors()->add(
                'end_datetime',
                'Schedule must be at least 15 minutes long.'
            );
        }
    }

    /**
     * Prevent double-booking the same crew member in overlapping time slots.
     */
    protected function validateCrewOverlap($validator): void
    {
        $scheduleId = $this->route('id') ?? $this->route('schedule');
        $schedule = Schedule::find($scheduleId);
        if (!$schedule) {
            return;
        }

        $crewId = $this->has('crew_id') ? $this->input('crew_id') : $schedule->crew_id;
        if (!$crewId) {
            return;
        }

        $vendorId = auth()->user()->vendor_id;
        $start = $this->has('start_datetime') ? $this->input('start_datetime') : $schedule->start_datetime;
        $end = $this->has('end_datetime') ? $this->input('end_datetime') : $schedule->end_datetime;

        $overlap = Schedule::where('vendor_id', $vendorId)
            ->where('crew_id', $crewId)
            ->where('id', '!=', $scheduleId)
            ->whereNotIn('status', ['cancelled'])
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->exists();

        if ($overlap) {
            $validator->errors()->add(
                'crew_id',
                'This crew member already has a schedule that overlaps with the selected time slot.'
            );
        }
    }
}
