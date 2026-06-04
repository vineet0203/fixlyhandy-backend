<?php

namespace App\Facades;

use App\Services\Logging\SystemLoggerService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void log(string $message, string $level = 'info', array $context = [], string $category = 'system', array $notifyVia = [], bool $storeInDatabase = true)
 * @method static void logError(\Throwable $exception, array $additionalContext = [], array $notifyVia = ['email'], string $category = 'error')
 * @method static void logUserAction(string $action, ?int $userId = null, array $metadata = [], string $level = 'info')
 * @method static void logAutomatedTask(string $taskName, string $status, array $details = [], string $level = 'info', array $notifyVia = [])
 * @method static void logAdminAlert(string $message, string $actionRequired = null, array $context = [], array $notifyVia = ['email', 'database'])
 * @method static int cleanOldLogs(int $daysToKeep = 30)
 * @method static array getStatistics(array $filters = [])
 *
 * @see \App\Services\Logging\SystemLoggerService
 */
class SystemLogger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SystemLoggerService::class;
    }
}
