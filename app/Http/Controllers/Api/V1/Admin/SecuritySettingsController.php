<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\PasswordSecuritySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecuritySettingsController extends BaseController
{
    /**
     * Get current security settings
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = PasswordSecuritySetting::getGlobalSettings();

            return $this->successResponse(
                $settings,
                'Security settings retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to get security settings', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to retrieve security settings', 500);
        }
    }

    /**
     * Update security settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'min_length' => 'integer|min:6|max:32',
                'require_uppercase' => 'boolean',
                'require_lowercase' => 'boolean',
                'require_numbers' => 'boolean',
                'require_symbols' => 'boolean',
                'password_expiry_days' => 'integer|min:0|max:365',
                'password_history_size' => 'integer|min:0|max:20',
                'max_login_attempts' => 'integer|min:1|max:20',
                'lockout_duration_minutes' => 'integer|min:1|max:1440',
                'force_password_change_on_first_login' => 'boolean',
                'notify_on_password_change' => 'boolean',
                'require_mfa' => 'boolean',
            ]);

            $success = PasswordSecuritySetting::updateGlobalSettings($validated);

            if ($success) {
                return $this->successResponse(
                    PasswordSecuritySetting::getGlobalSettings(),
                    'Security settings updated successfully'
                );
            }

            return $this->errorResponse('Failed to update security settings', 500);
        } catch (\Exception $e) {
            Log::error('Failed to update security settings', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to update security settings', 400);
        }
    }
}
