<?php

namespace App\Http\Requests\Api\V1\Dispatch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDispatchScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vendorId = auth()->user()?->vendor_id;

        return [
            'job_id' => [
                'required',
                'integer',
                Rule::exists('jobs', 'id')->where(fn ($query) => $query->where('vendor_id', $vendorId)),
            ],
            'crew_id' => [
                'nullable',
                'integer',
                Rule::exists('crews', 'id'),
            ],
            'employee_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('vendor_id', $vendorId)),
            ],
            'title' => 'nullable|string|max:255',
            'schedule_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => ['nullable', Rule::in(['pending', 'assigned', 'in_progress', 'completed', 'cancelled'])],
            'priority' => ['nullable', Rule::in(['normal', 'high', 'emergency'])],
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lng' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
