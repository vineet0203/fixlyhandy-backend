<?php

namespace App\Http\Requests\Api\V1\Customers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerQuoteApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['Accepted', 'Rejected', 'accepted', 'rejected'])],
            'customer_signature' => ['nullable', 'string'],
        ];
    }
}
