<?php

namespace App\Console\Commands;

use App\Models\PasswordSecuritySetting;
use Illuminate\Console\Command;

class ManagePasswordSecuritySettings extends Command
{
    protected $signature = 'security:password-settings 
                            {action : show|update|reset}
                            {--min_length= : Minimum password length}
                            {--expiry_days= : Password expiry days}
                            {--history_size= : Password history size}
                            {--max_attempts= : Maximum login attempts}
                            {--lockout_minutes= : Lockout duration in minutes}';

    protected $description = 'Manage global password security settings';

    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'show':
                return $this->showSettings();
            case 'update':
                return $this->updateSettings();
            case 'reset':
                return $this->resetSettings();
            default:
                $this->error("Invalid action. Use: show, update, or reset");
                return 1;
        }
    }

    private function showSettings(): int
    {
        $settings = PasswordSecuritySetting::getGlobalSettings();

        $this->info('📋 Global Password Security Settings');
        $this->info('====================================');

        $this->table(
            ['Setting', 'Value'],
            [
                ['Minimum Length', $settings->min_length],
                ['Require Uppercase', $settings->require_uppercase ? 'Yes' : 'No'],
                ['Require Lowercase', $settings->require_lowercase ? 'Yes' : 'No'],
                ['Require Numbers', $settings->require_numbers ? 'Yes' : 'No'],
                ['Require Symbols', $settings->require_symbols ? 'Yes' : 'No'],
                ['Password Expiry Days', $settings->password_expiry_days],
                ['Password History Size', $settings->password_history_size],
                ['Max Login Attempts', $settings->max_login_attempts],
                ['Lockout Duration (minutes)', $settings->lockout_duration_minutes],
                ['Force Change on First Login', $settings->force_password_change_on_first_login ? 'Yes' : 'No'],
                ['Notify on Password Change', $settings->notify_on_password_change ? 'Yes' : 'No'],
                ['Require MFA', $settings->require_mfa ? 'Yes' : 'No'],
            ]
        );

        return 0;
    }

    private function updateSettings(): int
    {
        $settings = PasswordSecuritySetting::getGlobalSettings();

        $updates = [];

        if ($this->option('min_length')) {
            $updates['min_length'] = (int) $this->option('min_length');
        }

        if ($this->option('expiry_days')) {
            $updates['password_expiry_days'] = (int) $this->option('expiry_days');
        }

        if ($this->option('history_size')) {
            $updates['password_history_size'] = (int) $this->option('history_size');
        }

        if ($this->option('max_attempts')) {
            $updates['max_login_attempts'] = (int) $this->option('max_attempts');
        }

        if ($this->option('lockout_minutes')) {
            $updates['lockout_duration_minutes'] = (int) $this->option('lockout_minutes');
        }

        if (empty($updates)) {
            $this->warn('No updates specified. Use options like --min_length=10 --expiry_days=60');
            return 1;
        }

        if ($settings->update($updates)) {
            $this->info('✅ Security settings updated successfully!');
            return 0;
        }

        $this->error('Failed to update security settings');
        return 1;
    }

    private function resetSettings(): int
    {
        if (!$this->confirm('Are you sure you want to reset to default security settings?')) {
            $this->info('Reset cancelled.');
            return 0;
        }

        $defaults = [
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
        ];

        PasswordSecuritySetting::where('type', 'global')->delete();
        PasswordSecuritySetting::create(array_merge(['type' => 'global'], $defaults));

        $this->info('✅ Security settings reset to defaults!');
        return 0;
    }
}
