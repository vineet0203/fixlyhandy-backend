<?php
// app/Http/Requests/Api/V1/Jobs/GetJobsRequest.php

namespace App\Http\Requests\Api\V1\Jobs;

use Illuminate\Foundation\Http\FormRequest;

class GetJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'work_type' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'assigned_to' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|in:created_at,start_date,end_date,total_amount,status',
            'sort_order' => 'nullable|in:asc,desc',
        ];
    }
}