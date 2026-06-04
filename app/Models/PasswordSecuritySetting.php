<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PasswordSecuritySetting extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'type',
        'min_length',
        'require_uppercase',
        'require_lowercase',
        'require_numbers',
        'require_symbols',
        'password_expiry_days',
        'password_history_size',
        'max_login_attempts',
        'lockout_duration_minutes',
        'force_password_change_on_first_login',
        'notify_on_password_change',
        'require_mfa',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'require_uppercase' => 'boolean',
        'require_lowercase' => 'boolean',
        'require_numbers' => 'boolean',
        'require_symbols' => 'boolean',
        'force_password_change_on_first_login' => 'boolean',
        'notify_on_password_change' => 'boolean',
        'require_mfa' => 'boolean'
    ];

    /**
     * Get global security settings
     */
    public static function getGlobalSettings(): self
    {
        $settings = static::where('type', 'global')->first();

        if (!$settings) {
            // Create default global settings if they don't exist
            // Use DB facade to bypass model events
            $id = DB::table('password_security_settings')->insertGetId([
                'type' => 'global',
                'min_length' => 8,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => true,
                'password_expiry_days' => 90,
                'password_history_size' => 5,
                'max_login_attempts' => 5,
                'lockout_duration_minutes' => 15,
                'force_password_change_on_first_login' => false,
                'notify_on_password_change' => true,
                'require_mfa' => false,
                'created_by' => 1, // Explicitly set system user
                'updated_by' => 1, // Explicitly set system user
                'createdon' => now(),
                'modifiedon' => now(),
            ]);

            $settings = static::find($id);
        }

        return $settings;
    }

    /**
     * Get password validation rules
     */
    public function getPasswordRules(): array
    {
        $rules = ['required', 'string', 'confirmed'];

        $rules[] = 'min:' . $this->min_length;

        if ($this->require_uppercase) {
            $rules[] = 'regex:/[A-Z]/';
        }

        if ($this->require_lowercase) {
            $rules[] = 'regex:/[a-z]/';
        }

        if ($this->require_numbers) {
            $rules[] = 'regex:/[0-9]/';
        }

        if ($this->require_symbols) {
            $rules[] = 'regex:/[\W_]/';
        }

        return $rules;
    }

    /**
     * Update global settings
     */
    public static function updateGlobalSettings(array $data): bool
    {
        $settings = static::getGlobalSettings();
        return $settings->update($data);
    }
}
