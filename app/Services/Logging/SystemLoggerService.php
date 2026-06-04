<?php

namespace App\Services\Logging;

use App\Jobs\SendSystemAlertEmail;
use App\Models\SystemErrorLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SystemLoggerService
{
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    /**
     * Notification methods
     */
    const NOTIFY_EMAIL = 'email';
    const NOTIFY_SLACK = 'slack';
    const NOTIFY_DATABASE = 'database';
    const NOTIFY_ALL = 'all';

    private array $lastEmailSent = [];

    /**
     * Log an event with optional notification
     */
    public function log(
        string $message,
        string $level = self::LEVEL_INFO,
        array $context = [],
        string $category = 'system',
        array $notifyVia = [],
        bool $storeInDatabase = true
    ): void {
        try {
            // Add timestamp and request info
            $context['timestamp'] = now()->toISOString();


            $context['request_id'] = session()->getId() ?: uniqid('req_', true);

            // Check if request() is available (it might not be in CLI/queue)
            if (app()->runningInConsole()) {
                $context['ip_address'] = 'cli';
                $context['user_agent'] = 'console';
                $context['url'] = 'cli';
                $context['method'] = 'cli';
            } else {
                try {
                    $context['ip_address'] = request()->ip();
                    $context['user_agent'] = request()->userAgent() ?? 'unknown';
                    $context['url'] = request()->fullUrl();
                    $context['method'] = request()->method();
                } catch (\Exception $e) {
                    $context['ip_address'] = 'unknown';
                    $context['user_agent'] = 'unknown';
                    $context['url'] = 'unknown';
                    $context['method'] = 'unknown';
                }
            }

            // Log to Laravel's log system
            $this->logToLaravel($message, $level, $context);

            // Store in database if enabled
            if ($storeInDatabase) {
                $this->storeInDatabase($message, $level, $context, $category);
            }

            // Send notifications if needed
            if (!empty($notifyVia)) {
                $this->sendNotifications($message, $level, $context, $category, $notifyVia);
            }
        } catch (\Exception $e) {
            // Fallback to basic logging if our logging system fails
            Log::error('Failed to log using SystemLoggerService: ' . $e->getMessage());
        }
    }

    /**
     * Log error with notification
     */
    public function logError(
        \Throwable $exception,
        array $additionalContext = [],
        array $notifyVia = [self::NOTIFY_EMAIL],
        string $category = 'error'
    ): void {
        $context = array_merge([
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => config('app.debug') ? $exception->getTraceAsString() : null,
        ], $additionalContext);

        $this->log(
            'Exception occurred: ' . $exception->getMessage(),
            self::LEVEL_ERROR,
            $context,
            $category,
            $notifyVia
        );
    }

    /**
     * Log user action
     */
    public function logUserAction(
        string $action,
        ?int $userId = null,
        array $metadata = [],
        string $level = self::LEVEL_INFO
    ): void {
        $context = [
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'metadata' => $metadata,
        ];

        $this->log(
            "User action: {$action}",
            $level,
            $context,
            'user_action'
        );
    }

    /**
     * Log automated job/task
     */
    public function logAutomatedTask(
        string $taskName,
        string $status,
        array $details = [],
        string $level = self::LEVEL_INFO,
        array $notifyVia = []
    ): void {
        $context = [
            'task_name' => $taskName,
            'status' => $status,
            'details' => $details,
        ];

        $this->log(
            "Automated task {$taskName}: {$status}",
            $level,
            $context,
            'automated_task',
            $notifyVia
        );
    }

    /**
     * Log system event that requires admin attention
     */
    public function logAdminAlert(
        string $message,
        string $actionRequired = null,
        array $context = [],
        array $notifyVia = [self::NOTIFY_EMAIL, self::NOTIFY_DATABASE]
    ): void {
        $context['action_required'] = $actionRequired;

        $this->log(
            $message,
            self::LEVEL_WARNING,
            $context,
            'admin_alert',
            $notifyVia
        );
    }

    /**
     * Clear old logs (can be run as scheduled job)
     */
    public function cleanOldLogs(int $daysToKeep = 30): int
    {
        return SystemErrorLog::where('created_at', '<', now()->subDays($daysToKeep))
            ->delete();
    }

    /**
     * Get statistics about logs
     */
    public function getStatistics(array $filters = []): array
    {
        $query = SystemErrorLog::query();

        if (isset($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return [
            'total_logs' => $query->count(),
            'by_level' => $query->groupBy('level')->selectRaw('level, COUNT(*) as count')->get(),
            'by_category' => $query->groupBy('category')->selectRaw('category, COUNT(*) as count')->get(),
            'recent_logs' => $query->latest()->limit(10)->get(),
        ];
    }

    /**
     * Private method: Log to Laravel's system
     */
    private function logToLaravel(string $message, string $level, array $context): void
    {
        switch ($level) {
            case self::LEVEL_DEBUG:
                Log::debug($message, $context);
                break;
            case self::LEVEL_INFO:
                Log::info($message, $context);
                break;
            case self::LEVEL_WARNING:
                Log::warning($message, $context);
                break;
            case self::LEVEL_ERROR:
                Log::error($message, $context);
                break;
            case self::LEVEL_CRITICAL:
                Log::critical($message, $context);
                break;
            default:
                Log::info($message, $context);
        }
    }

    /**
     * Private method: Store in database
     */
    private function storeInDatabase(string $message, string $level, array $context, string $category): void
    {
        try {
            SystemErrorLog::create([
                'level' => $level,
                'category' => $category,
                'message' => $message,
                'context' => $context,
                'created_by' => auth()->id(),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ]);
        } catch (\Exception $e) {
            // Log the failure but don't throw
            Log::error('Failed to store log in database: ' . $e->getMessage());
        }
    }

    /**
     * Private method: Send notifications
     */
    private function sendNotifications(string $message, string $level, array $context, string $category, array $notifyVia): void
    {
        // Check if notifications should be sent for this level/category
        if (!$this->shouldNotify($level, $category)) {
            return;
        }

        foreach ($notifyVia as $method) {
            try {
                switch ($method) {
                    case self::NOTIFY_EMAIL:
                        $this->sendEmailNotification($message, $level, $context, $category);
                        break;
                    case self::NOTIFY_SLACK:
                        $this->sendSlackNotification($message, $level, $context, $category);
                        break;
                    case self::NOTIFY_DATABASE:
                        $this->sendDatabaseNotification($message, $level, $context, $category);
                        break;
                }
            } catch (\Exception $e) {
                Log::error("Failed to send {$method} notification: " . $e->getMessage());
            }
        }
    }

    /**
     * Check if notification should be sent based on level and category
     */
    private function shouldNotify(string $level, string $category): bool
    {
        $config = config('logging.notification_rules', [
            'error' => ['error', 'critical'],
            'categories' => ['admin_alert', 'error', 'critical'],
        ]);

        $levelsToNotify = $config['error'] ?? ['error', 'critical'];
        $categoriesToNotify = $config['categories'] ?? ['admin_alert', 'error', 'critical'];

        return in_array($level, $levelsToNotify) && in_array($category, $categoriesToNotify);
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(string $message, string $level, array $context, string $category): void
    {
        $adminEmail = $this->getAdminEmail();

        if (!$adminEmail) {
            return;
        }

        // Check throttle
        $key = md5($message . $level . $category);
        $throttleMinutes = config('logging.system_logger.email.throttle_minutes', 5);

        if (
            isset($this->lastEmailSent[$key]) &&
            $this->lastEmailSent[$key]->addMinutes($throttleMinutes)->isFuture()
        ) {
            return;
        }

        $this->lastEmailSent[$key] = now();

        // Dispatch to system_alerts queue with high priority
        SendSystemAlertEmail::dispatch($message, $level, $context, $category, $adminEmail)
            ->onQueue('system_alerts');
    }

    /**
     * Send Slack notification (placeholder - implement based on your Slack setup)
     */
    private function sendSlackNotification(string $message, string $level, array $context, string $category): void
    {
        // Implement Slack webhook integration here
        // Example:
        // $webhookUrl = config('services.slack.webhook_url');
        // Http::post($webhookUrl, ['text' => $message, ...]);
    }

    /**
     * Send database notification (store in notifications table)
     */
    private function sendDatabaseNotification(string $message, string $level, array $context, string $category): void
    {
        $adminUsers = $this->getAdminUsers();

        if ($adminUsers->isEmpty()) {
            return;
        }

        $notificationData = [
            'type' => 'system_alert',
            'data' => [
                'message' => $message,
                'level' => $level,
                'category' => $category,
                'context' => $context,
                'timestamp' => now()->toISOString(),
            ],
        ];

        foreach ($adminUsers as $admin) {
            $admin->notifications()->create([
                'type' => 'system_alert',
                'data' => $notificationData,
                'read_at' => null,
            ]);
        }
    }

    /**
     * Get admin email from config
     */
    private function getAdminEmail(): ?string
    {
        $adminEmail = config('notification.admin.email') ??
            config('mail.admin_email') ??
            env('MAIL_ADMIN_EMAIL');

        return $adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL) ? $adminEmail : null;
    }

    /**
     * Get admin users
     */
    private function getAdminUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\User::whereHas('roles', function ($query) {
            $query->where('slug', 'platform_super_admin');
        })->where('is_active', true)->get();
    }

    /**
     * Format email content
     */
    private function formatEmailContent(string $message, string $level, array $context, string $category): string
    {
        $appName = config('app.name');
        $env = config('app.env');
        $url = config('app.url');

        $content = "{$appName} - System Alert\n";
        $content .= "Environment: {$env}\n";
        $content .= "URL: {$url}\n";
        $content .= "Time: " . now()->toDateTimeString() . "\n";
        $content .= "Level: {$level}\n";
        $content .= "Category: {$category}\n";
        $content .= "Message: {$message}\n\n";
        $content .= "Context:\n" . json_encode($context, JSON_PRETTY_PRINT) . "\n\n";
        $content .= "---\n";
        $content .= "This is an automated message from {$appName} system monitoring.\n";

        return $content;
    }
}
