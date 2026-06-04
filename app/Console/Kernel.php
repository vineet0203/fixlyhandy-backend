<?php

namespace App\Console;

use App\Models\AuditLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\ManagePasswordSecuritySettings::class,
        \App\Console\Commands\CleanTempFiles::class,
        \App\Console\Commands\MonitorDiskSpace::class,
        \App\Console\Commands\DeploymentReport::class,
        \App\Console\Commands\SystemHealthCheck::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ==================== SECURITY & AUDIT ====================
        
        // Clean old audit logs daily at 2 AM
        $schedule->command('model:prune', [
            '--model' => [AuditLog::class],
        ])->dailyAt('02:00')->description('Clean old audit logs');
        
        // Check password expiry daily at 3 AM
        $schedule->command('security:check-password-expiry')
            ->dailyAt('03:00')
            ->description('Check password expiry');
        
        // Security scan weekly
        $schedule->command('security:scan')
            ->weekly()->mondays()->at('04:00')
            ->description('Weekly security scan');
        
        // ==================== UPLOADS & STORAGE ====================
        
        // Cleanup temporary uploads every hour
        $schedule->command('uploads:cleanup')
            ->hourly()
            ->description('Clean temporary uploads');
        
        // Clean temp files daily at 1 AM
        $schedule->command('clean:temp --hours=24')
            ->dailyAt('01:00')
            ->description('Clean temp files');
        
        // ==================== QUEUE MANAGEMENT ====================
        
        // Restart queue workers daily at 3 AM
        $schedule->command('queue:restart')
            ->dailyAt('03:00')
            ->description('Restart queue workers');
        
        // Prune old failed jobs (older than 48 hours)
        $schedule->command('queue:prune-failed --hours=48')
            ->dailyAt('02:30')
            ->description('Clean failed jobs');
        
        // Prune old batches
        $schedule->command('queue:prune-batches --hours=72 --unfinished=96')
            ->dailyAt('02:45')
            ->description('Clean old batches');
        
        // ==================== DEPLOYMENT & BACKUP ====================
        
        // Send daily deployment report at 9 AM
        $schedule->command('deployments:report --days=1')
            ->dailyAt('09:00')
            ->description('Daily deployment report');
        
        // Clean old deployment backups (keep 7 days)
        $schedule->call(function () {
            $backupPath = storage_path('backups');
            if (file_exists($backupPath)) {
                $files = glob($backupPath . '/*');
                $now = time();
                
                foreach ($files as $file) {
                    if (is_dir($file)) {
                        if ($now - filemtime($file) >= 7 * 24 * 60 * 60) {
                            exec("rm -rf " . escapeshellarg($file));
                            \Log::info('Cleaned old backup: ' . $file);
                        }
                    }
                }
            }
        })->dailyAt('04:00')->description('Clean old backups');
        
        // ==================== DATABASE MAINTENANCE ====================
        
        // Database optimization weekly
        $schedule->command('db:optimize')
            ->weekly()->sundays()->at('02:00')
            ->description('Weekly DB optimization');
        
        // Database backup (if using spatie/laravel-backup)
        // $schedule->command('backup:run --only-db')
        //     ->dailyAt('01:00')
        //     ->description('Daily database backup');
        
        // ==================== SYSTEM MONITORING ====================
        
        // Health check every hour
        $schedule->command('health:check --fresh')
            ->hourly()
            ->description('Application health check');
        
        // Monitor disk space every hour
        $schedule->command('monitor:disk-space --threshold=90')
            ->hourly()
            ->description('Monitor disk space');
        
        // Monitor memory usage every hour
        $schedule->command('monitor:memory --threshold=80')
            ->hourly()
            ->description('Monitor memory usage');
        
        // Check for expired sessions daily
        $schedule->command('session:gc')
            ->dailyAt('06:00')
            ->description('Clean expired sessions');
        
        // ==================== CACHE MAINTENANCE ====================
        
        // Clear expired cache tags hourly
        $schedule->command('cache:prune-stale-tags')
            ->hourly()
            ->description('Prune cache tags');
        
        // Clear view cache daily
        $schedule->command('view:clear')
            ->dailyAt('05:00')
            ->description('Clear view cache');
        
        // ==================== NOTIFICATIONS ====================
        
        // Send system status report weekly
        $schedule->command('system:status-report')
            ->weekly()->mondays()->at('08:00')
            ->description('Weekly system report');
        
        // ==================== APPLICATION SPECIFIC ====================
        
        // Example: Send daily user activity report
        // $schedule->command('reports:daily-activity')
        //     ->dailyAt('17:00')
        //     ->description('Daily activity report');
        
        // Example: Process pending jobs
        // $schedule->command('process:pending-jobs')
        //     ->everyFiveMinutes()
        //     ->description('Process pending jobs');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
    
    /**
     * Get the timezone that should be used by default for scheduled events.
     */
    protected function scheduleTimezone(): string
    {
        return config('app.timezone', 'UTC');
    }
}