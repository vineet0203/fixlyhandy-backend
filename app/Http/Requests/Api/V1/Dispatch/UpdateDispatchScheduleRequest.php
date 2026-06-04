<?php

namespace App\Http\Requests\Api\V1\Dispatch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDispatchScheduleRequest extends FormRequest
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
                'sometimes',
                'integer',
                Rule::exists('jobs', 'id')->where(fn ($query) => $query->where('vendor_id', $vendorId)),
            ],
            'crew_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('crews', 'id'),
            ],
            'employee_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('vendor_id', $vendorId)),
            ],
            'title' => 'sometimes|nullable|string|max:255',
            'schedule_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'status' => ['sometimes', Rule::in(['pending', 'assigned', 'in_progress', 'completed', 'cancelled'])],
            'priority' => ['sometimes', Rule::in(['normal', 'high', 'emergency'])],
            'location_lat' => 'sometimes|nullable|numeric|between:-90,90',
            'location_lng' => 'sometimes|nullable|numeric|between:-180,180',
            'address' => 'sometimes|nullable|string|max:2000',
            'notes' => 'sometimes|nullable|string|max:2000',
        ];
    }
}
