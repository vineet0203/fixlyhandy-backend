<?php

namespace App\Http\Requests\Api\V1\Customers;

use Illuminate\Foundation\Http\FormRequest;

class CustomerQuoteDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['nullable', 'in:approve,reject,submit'],
            'approved_price' => ['nullable', 'numeric', 'min:0'],
            'signature' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
