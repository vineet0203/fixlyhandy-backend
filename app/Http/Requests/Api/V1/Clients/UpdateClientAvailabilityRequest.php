<?php

namespace App\Http\Requests\Api\V1\Clients;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add your authorization logic
    }

    public function rules(): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return [
            'available_days' => ['sometimes', 'array', 'min:1'],
            'available_days.*' => ['string', Rule::in($days)],
            
            'preferred_start_time' => ['sometimes', 'date_format:H:i'],
            'preferred_end_time' => ['sometimes', 'date_format:H:i', 'after:preferred_start_time'],
            
            'has_lunch_break' => ['sometimes', 'boolean'],
            'lunch_start' => ['required_if:has_lunch_break,true', 'date_format:H:i', 'nullable'],
            'lunch_end' => ['required_if:has_lunch_break,true', 'date_format:H:i', 'after:lunch_start', 'nullable'],
            
            'schedule_start_date' => ['sometimes', 'date'],
            'schedule_end_date' => ['nullable', 'date', 'after:schedule_start_date'],
            
            'service_type' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'available_days.array' => 'Available days must be an array.',
            'available_days.min' => 'Please select at least one available day.',
            'available_days.*.in' => 'Invalid day selected. Valid days are: Monday to Sunday.',
            
            'preferred_start_time.date_format' => 'Start time must be in HH:mm format.',
            'preferred_end_time.date_format' => 'End time must be in HH:mm format.',
            'preferred_end_time.after' => 'End time must be after start time.',
            
            'has_lunch_break.boolean' => 'Lunch break must be true or false.',
            'lunch_start.required_if' => 'Lunch start time is required when lunch break is enabled.',
            'lunch_start.date_format' => 'Lunch start time must be in HH:mm format.',
            'lunch_end.required_if' => 'Lunch end time is required when lunch break is enabled.',
            'lunch_end.date_format' => 'Lunch end time must be in HH:mm format.',
            'lunch_end.after' => 'Lunch end time must be after lunch start time.',
            
            'schedule_start_date.date' => 'Schedule start date must be a valid date.',
            'schedule_end_date.date' => 'Schedule end date must be a valid date.',
            'schedule_end_date.after' => 'Schedule end date must be after start date.',
            
            'service_type.max' => 'Service type cannot exceed 100 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'is_active.boolean' => 'Active status must be true or false.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('has_lunch_break')) {
            $this->merge([
                'has_lunch_break' => filter_var($this->has_lunch_break, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
        
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}