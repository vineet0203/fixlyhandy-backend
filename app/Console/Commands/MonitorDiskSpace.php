<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorDiskSpace extends Command
{
    protected $signature = 'monitor:disk-space {--threshold=90 : Alert when usage exceeds %}';
    protected $description = 'Monitor disk space usage and send alerts';

    public function handle()
    {
        $threshold = $this->option('threshold');
        
        // Check multiple partitions
        $partitions = [
            '/' => 'Root',
            '/var' => 'Var',
            '/home' => 'Home',
            '/var/www' => 'Web',
        ];
        
        $alerts = [];
        $status = [];
        
        foreach ($partitions as $path => $name) {
            if (!file_exists($path)) {
                continue;
            }
            
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            
            if ($total === false || $free === false) {
                continue;
            }
            
            $used = $total - $free;
            $percentage = $total > 0 ? round(($used / $total) * 100, 2) : 0;
            
            $data = [
                'partition' => $name,
                'path' => $path,
                'total_gb' => round($total / 1024 / 1024 / 1024, 2),
                'free_gb' => round($free / 1024 / 1024 / 1024, 2),
                'used_gb' => round($used / 1024 / 1024 / 1024, 2),
                'percentage' => $percentage,
                'status' => $percentage > $threshold ? 'critical' : ($percentage > 80 ? 'warning' : 'healthy'),
            ];
            
            $status[] = $data;
            
            if ($percentage > $threshold) {
                $alerts[] = [
                    'partition' => $name,
                    'path' => $path,
                    'percentage' => $percentage,
                    'free_gb' => $data['free_gb'],
                ];
            }
            
            $this->line("{$name} ({$path}): {$percentage}% used, {$data['free_gb']}GB free");
        }
        
        // Log status
        Log::info('Disk space monitoring', ['partitions' => $status]);
        
        // Send alerts if any
        if (!empty($alerts)) {
            $alertMessage = "🚨 *Disk Space Alert*\n\n";
            
            foreach ($alerts as $alert) {
                $alertMessage .= "• {$alert['partition']} ({$alert['path']}): {$alert['percentage']}% used\n";
                $alertMessage .= "  Only {$alert['free_gb']}GB free remaining\n\n";
            }
            
            $alertMessage .= "Threshold: {$threshold}%\n";
            $alertMessage .= "Time: " . now()->toDateTimeString();
            
            // Log critical
            Log::critical('Disk space critical', ['alerts' => $alerts]);
            
            // Send Telegram alert if configured
            if (class_exists('App\Services\TelegramService')) {
                try {
                    $telegram = new \App\Services\TelegramService();
                    $telegram->sendRawMessage($alertMessage);
                    $this->info("⚠️ Disk space alert sent to Telegram");
                } catch (\Exception $e) {
                    $this->error("Failed to send Telegram alert: " . $e->getMessage());
                }
            }
            
            // Also send email alert if configured
            $alertEmails = env('ALERT_EMAILS');
            if ($alertEmails) {
                $emails = explode(',', $alertEmails);
                foreach ($emails as $email) {
                    // You could implement email sending here
                    $this->line("Alert email would be sent to: " . trim($email));
                }
            }
            
            $this->error("❌ Disk space critical on some partitions!");
            return 1;
        }
        
        $this->info("✅ All disk partitions are within limits (threshold: {$threshold}%)");
        return 0;
    }
}