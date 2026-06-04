<?php
// app/Http/Requests/Api/V1/Jobs/UpdateJobRequest.php

namespace App\Http\Requests\Api\V1\Jobs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'sometimes|exists:clients,id',
            'assigned_to' => 'nullable|exists:users,id',
            
            'work_type' => ['sometimes', Rule::in([
                'one_time', 'recurring', 'maintenance', 'emergency',
                'installation', 'repair', 'consultation', 'other'
            ])],
            
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high', 'urgent'])],
            
            'status' => ['sometimes', Rule::in([
                'pending', 'scheduled', 'in_progress', 'on_hold',
                'completed', 'cancelled', 'archived'
            ])],
            
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_completion_date' => 'nullable|date',
            'actual_completion_date' => 'nullable|date',
            
            'estimated_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            
            'location_type' => ['nullable', Rule::in(['office', 'remote', 'client_site', 'other'])],
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            
            'instructions' => 'nullable|string',
            'notes' => 'nullable|string',
            
            // Tasks
            'tasks' => 'nullable|array',
            'tasks.*.id' => 'nullable|exists:job_tasks,id',
            'tasks.*.name' => 'required_with:tasks|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.completed' => 'nullable|boolean',
            'tasks.*.due_date' => 'nullable|date',
            'tasks.*._delete' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists' => 'Selected client does not exist.',
            'assigned_to.exists' => 'Selected user does not exist.',
            'work_type.in' => 'Invalid work type selected.',
            'priority.in' => 'Invalid priority selected.',
            'status.in' => 'Invalid status selected.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'estimated_amount.min' => 'Estimated amount cannot be negative.',
            'total_amount.min' => 'Total amount cannot be negative.',
            'deposit_amount.min' => 'Deposit amount cannot be negative.',
            'paid_amount.min' => 'Paid amount cannot be negative.',
            'tasks.*.name.required_with' => 'Task name is required.',
            'tasks.*.due_date.date' => 'Invalid due date format.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('paid_amount')) {
            $Job = $this->route('job');
            if ($Job) {
                $data['balance_due'] = $Job->total_amount - $this->paid_amount;
            }
        }

        $this->merge($data);
    }
}