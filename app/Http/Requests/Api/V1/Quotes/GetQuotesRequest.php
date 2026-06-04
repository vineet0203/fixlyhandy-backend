<?php

namespace App\Http\Requests\Api\V1\Quotes;

use Illuminate\Foundation\Http\FormRequest;

class GetQuotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,sent,pending,approved,rejected,expired,all',
            'client_email' => 'nullable|email',
            'client_name' => 'nullable|string|max:191',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'follow_up_status' => 'nullable|in:scheduled,completed,cancelled',
            'sort_by' => 'nullable|in:id,quote_number,title,client_name,total_amount,created_at,updated_at,expires_at',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'per_page.max' => 'Maximum 100 records per page allowed.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->search ? trim($this->search) : null,
            'sort_by' => $this->sort_by ?: 'created_at',
            'sort_order' => $this->sort_order ?: 'desc',
            'per_page' => $this->per_page ? (int) $this->per_page : 15,
        ]);
    }
}