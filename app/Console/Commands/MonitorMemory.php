<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MonitorMemory extends Command
{
    protected $signature = 'monitor:memory {--threshold=80 : Alert when usage exceeds %}';
    protected $description = 'Monitor memory usage';

    public function handle()
    {
        $threshold = $this->option('threshold');
        
        // Get memory usage
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        // Convert memory limit to bytes
        $limitBytes = $this->convertToBytes($memoryLimit);
        $usagePercent = $limitBytes > 0 ? round(($memoryUsage / $limitBytes) * 100, 2) : 0;
        
        $data = [
            'limit' => $memoryLimit,
            'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'usage_percent' => $usagePercent,
            'threshold' => $threshold,
        ];
        
        if ($usagePercent > $threshold) {
            $this->error("⚠️ Memory usage high: {$usagePercent}%");
            
            \Log::warning('High memory usage', $data);
            
            if (class_exists('App\Services\TelegramService')) {
                $telegram = new \App\Services\TelegramService();
                $telegram->sendRawMessage(
                    "⚠️ *Memory Alert*\n" .
                    "Usage: {$usagePercent}%\n" .
                    "Current: {$data['usage_mb']}MB\n" .
                    "Peak: {$data['peak_mb']}MB\n" .
                    "Limit: {$memoryLimit}"
                );
            }
            
            return 1;
        }
        
        $this->info("✅ Memory usage OK: {$usagePercent}%");
        \Log::info('Memory check passed', $data);
        
        return 0;
    }
    
    private function convertToBytes($memoryLimit): int
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'g': return $value * 1024 * 1024 * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'k': return $value * 1024;
            default: return (int) $memoryLimit;
        }
    }
}