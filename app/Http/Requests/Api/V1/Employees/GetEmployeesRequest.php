<?php

namespace App\Http\Requests\Api\V1\Employees;

use Illuminate\Foundation\Http\FormRequest;

class GetEmployeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:191',
            'designation' => 'nullable|string|max:191',
            'is_active' => 'nullable|boolean',
            'reporting_manager_id' => 'nullable|integer',
            'gender' => 'nullable|in:male,female,other',
            'sort_by' => 'nullable|in:first_name,last_name,employee_id,email,department,designation,created_at',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}