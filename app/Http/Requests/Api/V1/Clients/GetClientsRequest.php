<?php

namespace App\Http\Requests\Api\V1\Clients;

use Illuminate\Foundation\Http\FormRequest;

class GetClientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:191',
            'business_type' => 'nullable|in:individual,sole_proprietorship,partnership,llc,corporation,non_profit,government,other',
            'service_category' => 'nullable|in:premium,regular,vip,strategic,new,at_risk',
            'status' => 'nullable|in:active,inactive,suspended,archived',
            'is_verified' => 'nullable|boolean',
            'city' => 'nullable|string|max:191',
            'state' => 'nullable|string|max:191',
            'country' => 'nullable|string|max:191',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date|after_or_equal:created_from',
            'sort_by' => 'nullable|in:id,business_name,contact_person_name,email,created_at,updated_at,status,service_category,business_type',
            'sort_order' => 'nullable|in:asc,desc',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->per_page ?? 15,
            'page' => $this->page ?? 1,
            'sort_by' => $this->sort_by ?? 'created_at',
            'sort_order' => $this->sort_order ?? 'desc',
            'is_verified' => $this->has('is_verified') ? filter_var($this->is_verified, FILTER_VALIDATE_BOOLEAN) : null,
        ]);
    }
}