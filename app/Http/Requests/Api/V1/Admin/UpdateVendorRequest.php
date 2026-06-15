<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['sometimes', 'required', 'string', 'max:255'],
            'website_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'business_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'service_category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'service_sub_category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'mobile_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_accepting_bookings' => ['sometimes', 'boolean'],
        ];
    }
}
