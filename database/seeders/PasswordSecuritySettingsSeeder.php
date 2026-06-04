<?php

namespace Database\Seeders;

use App\Models\PasswordSecuritySetting;
use Illuminate\Database\Seeder;

class PasswordSecuritySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing settings
        PasswordSecuritySetting::query()->delete();

        // Create single global security settings
        PasswordSecuritySetting::create([
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
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $this->command->info('✅ Global password security settings created successfully!');
        $this->command->info('Settings applied to ALL companies.');
    }
}
