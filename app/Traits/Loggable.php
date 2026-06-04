<?php

namespace App\Traits;

use App\Facades\SystemLogger;
use App\Services\Logging\SystemLoggerService;

trait Loggable
{
    /**
     * Log an error with context
     */
    protected function logError(\Throwable $e, array $context = [], string $category = null): void
    {
        $category = $category ?? $this->getDefaultLogCategory();

        SystemLogger::logError(
            $e,
            array_merge($context, $this->getDefaultLogContext()),
            [SystemLoggerService::NOTIFY_EMAIL],
            $category
        );
    }

    /**
     * Log user action
     */
    protected function logUserAction(string $action, array $metadata = []): void
    {
        SystemLogger::logUserAction(
            $action,
            auth()->id(),
            array_merge($metadata, $this->getDefaultLogContext()),
            SystemLoggerService::LEVEL_INFO
        );
    }

    /**
     * Log admin alert
     */
    protected function logAdminAlert(string $message, string $actionRequired = null, array $context = []): void
    {
        SystemLogger::logAdminAlert(
            $message,
            $actionRequired,
            array_merge($context, $this->getDefaultLogContext())
        );
    }

    /**
     * Get default log category based on class name
     */
    protected function getDefaultLogCategory(): string
    {
        $className = class_basename($this);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }

    /**
     * Get default log context
     */
    protected function getDefaultLogContext(): array
    {
        return [
            'class' => get_class($this),
            'timestamp' => now()->toISOString(),
            'request_id' => app()->runningInConsole()
                ? 'console-' . uniqid()
                : (session()->getId() ?: uniqid('req_', true)),
        ];
    }
}
