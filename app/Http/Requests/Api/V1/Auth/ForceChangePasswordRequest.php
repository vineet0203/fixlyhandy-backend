<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Models\PasswordSecuritySetting;
use Illuminate\Foundation\Http\FormRequest;

class ForceChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        // Get security settings from database
        $securitySettings = PasswordSecuritySetting::getGlobalSettings();
        
        $rules = [
            'new_password' => [
                'required',
                'string',
                'confirmed',
            ],
            'new_password_confirmation' => [
                'required',
                'string',
                'same:new_password'
            ]
        ];
        
        // Add password rules based on security settings
        $passwordRules = [];
        $passwordRules[] = 'min:' . $securitySettings->min_length;
        
        if ($securitySettings->require_uppercase) {
            $passwordRules[] = 'regex:/[A-Z]/';
        }
        
        if ($securitySettings->require_lowercase) {
            $passwordRules[] = 'regex:/[a-z]/';
        }
        
        if ($securitySettings->require_numbers) {
            $passwordRules[] = 'regex:/[0-9]/';
        }
        
        if ($securitySettings->require_symbols) {
            $passwordRules[] = 'regex:/[\W_]/';
        }
        
        $rules['new_password'] = array_merge($rules['new_password'], $passwordRules);
        
        return $rules;
    }

    public function messages(): array
    {
        $securitySettings = PasswordSecuritySetting::getGlobalSettings();
        
        return [
            'new_password.required' => 'New password is required',
            'new_password.confirmed' => 'Password confirmation does not match',
            'new_password.min' => 'Password must be at least ' . $securitySettings->min_length . ' characters',
            'new_password.regex' => 'Password must meet the security requirements',
            'new_password_confirmation.required' => 'Password confirmation is required',
            'new_password_confirmation.same' => 'Password confirmation does not match'
        ];
    }
}