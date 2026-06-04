<?php
// app/Http/Requests/Api/V1/Jobs/CreateJobRequest.php

namespace App\Http\Requests\Api\V1\Jobs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Section 1: Job Details
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'assigned_to' => 'nullable|exists:users,id',
            
            // Work order details
            'work_type' => ['required', Rule::in([
                'one_time', 'recurring', 'maintenance', 'emergency',
                'installation', 'repair', 'consultation', 'other'
            ])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'status' => ['sometimes', Rule::in([
                'pending', 'scheduled', 'in_progress', 'on_hold',
                'completed', 'cancelled', 'archived'
            ])],
            
            // Dates
            'issue_date' => 'required|date',
            'start_date' => 'nullable|date|after_or_equal:issue_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_completion_date' => 'nullable|date|after_or_equal:start_date',
            
            // Financial
            'currency' => 'required|string|size:3|in:USD,EUR,GBP,JPY,CAD,AUD',
            'estimated_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            
            // Location
            'location_type' => ['nullable', Rule::in(['office', 'remote', 'client_site', 'other'])],
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            
            // Additional info
            'instructions' => 'nullable|string',
            'notes' => 'nullable|string',
            
            // Tasks (checklist items)
            'tasks' => 'nullable|array',
            'tasks.*.name' => 'required_with:tasks|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.due_date' => 'nullable|date',
            
            // Attachments
            'attachments' => 'nullable|array',
            'attachments.*.file_name' => 'required_with:attachments|string',
            'attachments.*.file_path' => 'required_with:attachments|string',
            'attachments.*.file_type' => 'required_with:attachments|string',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Work order title is required.',
            'client_id.required' => 'Client is required.',
            'client_id.exists' => 'Selected client does not exist.',
            'work_type.required' => 'Work type is required.',
            'work_type.in' => 'Invalid work type selected.',
            'priority.required' => 'Priority is required.',
            'priority.in' => 'Invalid priority selected.',
            'issue_date.required' => 'Issue date is required.',
            'issue_date.date' => 'Invalid issue date format.',
            'start_date.after_or_equal' => 'Start date must be after or equal to issue date.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be 3 characters.',
            'currency.in' => 'Invalid currency selected.',
            'estimated_amount.min' => 'Estimated amount cannot be negative.',
            'total_amount.min' => 'Total amount cannot be negative.',
            'deposit_amount.min' => 'Deposit amount cannot be negative.',
            'paid_amount.min' => 'Paid amount cannot be negative.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [
            'job_number' => \App\Models\Job::generateJobNumber(),
            'status' => $this->status ?? 'pending',
            'currency' => strtoupper($this->currency ?? 'USD'),
            'issue_date' => $this->issue_date ?? now()->toDateString(),
            'estimated_amount' => $this->estimated_amount ?? 0,
            'total_amount' => $this->total_amount ?? 0,
            'deposit_amount' => $this->deposit_amount ?? 0,
            'paid_amount' => $this->paid_amount ?? 0,
        ];

        // If linked to a quote, fetch quote details
        if ($this->filled('quote_id')) {
            $quote = \App\Models\Quote::find($this->quote_id);
            if ($quote) {
                $data['client_id'] = $quote->client_id;
                $data['total_amount'] = $quote->total_amount;
                $data['currency'] = $quote->currency;
                $data['is_converted_from_quote'] = true;
            }
        }

        $this->merge($data);
    }
}