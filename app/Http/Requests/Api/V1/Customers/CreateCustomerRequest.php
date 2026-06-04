<?php

namespace App\Http\Requests\Api\V1\Customers;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CreateCustomerRequest extends FormRequest
{
    private ?string $existingRole = null;
    private ?string $existingRoleMessage = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('customers', 'email'),
                function ($attribute, $value, $fail) {
                    if (DB::table('employees')->where('email', $value)->exists()) {
                        $this->existingRole = 'employee';
                        $this->existingRoleMessage = 'This email is already registered as an Employee. Please use a different email.';
                        $fail($this->existingRoleMessage);
                    }
                },
            ],
            'phone' => ['required', 'string', 'max:20'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'phone.required' => 'Phone is required.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->existingRole && $this->existingRoleMessage) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $this->existingRoleMessage,
                'existing_role' => $this->existingRole,
                'errors' => $validator->errors(),
                'timestamp' => now()->toIso8601String(),
                'code' => 422,
            ], 422));
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
            'timestamp' => now()->toIso8601String(),
            'code' => 422,
        ], 422));
    }
}