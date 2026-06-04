<?php

use App\Facades\SystemLogger;

if (!function_exists('system_log')) {
    /**
     * Quick system log helper
     */
    function system_log(
        string $message,
        string $level = 'info',
        array $context = [],
        string $category = 'system'
    ): void {
        SystemLogger::log($message, $level, $context, $category);
    }
}

if (!function_exists('log_error')) {
    /**
     * Quick error logging helper
     */
    function log_error(\Throwable $e, array $context = [], string $category = 'error'): void
    {
        SystemLogger::logError($e, $context, ['email'], $category);
    }
}

if (!function_exists('log_admin_alert')) {
    /**
     * Quick admin alert helper
     */
    function log_admin_alert(string $message, string $actionRequired = null, array $context = []): void
    {
        SystemLogger::logAdminAlert($message, $actionRequired, $context);
    }
}
