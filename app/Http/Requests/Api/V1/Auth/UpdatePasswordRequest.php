<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Models\PasswordSecuritySetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // All authenticated users can update their password
    }

    public function rules(): array
    {
        // Get security settings from database
        $securitySettings = PasswordSecuritySetting::getGlobalSettings();

        // Build Password rule dynamically
        $passwordRule = Password::min($securitySettings->min_length);

        if ($securitySettings->require_uppercase) {
            $passwordRule = $passwordRule->letters()->mixedCase();
        } else if ($securitySettings->require_lowercase) {
            $passwordRule = $passwordRule->letters();
        }

        if ($securitySettings->require_numbers) {
            $passwordRule = $passwordRule->numbers();
        }

        if ($securitySettings->require_symbols) {
            $passwordRule = $passwordRule->symbols();
        }

        $passwordRule = $passwordRule->uncompromised();

        return [
            'current_password' => [
                'required',
                'string',
                'current_password:api'
            ],
            'new_password' => [
                'required',
                'string',
                'confirmed',
                $passwordRule
            ],
            'new_password_confirmation' => [
                'required',
                'string',
                'same:new_password'
            ]
        ];
    }

    public function messages(): array
    {
        $securitySettings = PasswordSecuritySetting::getGlobalSettings();

        return [
            'current_password.required' => 'Current password is required',
            'current_password.current_password' => 'Current password is incorrect',
            'new_password.required' => 'New password is required',
            'new_password.confirmed' => 'Password confirmation does not match',
            'new_password.min' => 'Password must be at least ' . $securitySettings->min_length . ' characters',
            'new_password.regex' => 'Password must meet the security requirements',
            'new_password_confirmation.required' => 'Password confirmation is required',
            'new_password_confirmation.same' => 'Password confirmation does not match'
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'current password',
            'new_password' => 'new password',
            'new_password_confirmation' => 'password confirmation'
        ];
    }
}
