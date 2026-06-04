<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class SystemHealthCheck extends Command
{
    protected $signature = 'health:check {--fresh : Run fresh checks}';
    protected $description = 'Check system health status';

    public function handle()
    {
        $this->info('🔍 Running system health checks...');
        
        $checks = [];
        
        // 1. Database connection
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => '✅', 'message' => 'Connected'];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => '❌', 'message' => $e->getMessage()];
        }
        
        // 2. Redis connection (if using)
        if (config('cache.default') === 'redis') {
            try {
                Redis::ping();
                $checks['redis'] = ['status' => '✅', 'message' => 'Connected'];
            } catch (\Exception $e) {
                $checks['redis'] = ['status' => '❌', 'message' => $e->getMessage()];
            }
        }
        
        // 3. Storage permissions
        $checks['storage'] = [
            'app' => is_writable(base_path()) ? '✅' : '❌',
            'storage' => is_writable(storage_path()) ? '✅' : '❌',
            'bootstrap' => is_writable(base_path('bootstrap/cache')) ? '✅' : '❌',
        ];
        
        // 4. Disk space
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        $percent = round(($used / $total) * 100, 2);
        
        $checks['disk'] = [
            'total' => round($total / 1024 / 1024 / 1024, 2) . ' GB',
            'free' => round($free / 1024 / 1024 / 1024, 2) . ' GB',
            'used' => $percent . '%',
            'status' => $percent > 90 ? '❌' : ($percent > 80 ? '⚠️' : '✅'),
        ];
        
        // 5. Memory usage
        $memory = memory_get_usage(true);
        $checks['memory'] = [
            'usage' => round($memory / 1024 / 1024, 2) . ' MB',
            'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
        ];
        
        // 6. Queue status
        try {
            $queueSize = \Illuminate\Support\Facades\Queue::size();
            $checks['queue'] = [
                'status' => '✅',
                'message' => "Queue size: {$queueSize}",
                'pending' => $queueSize,
            ];
        } catch (\Exception $e) {
            $checks['queue'] = ['status' => '❌', 'message' => $e->getMessage()];
        }
        
        // 7. Supervisor processes (check via system)
        exec('sudo supervisorctl status 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0) {
            $processes = [];
            foreach ($output as $line) {
                if (str_contains($line, 'RUNNING')) {
                    $processes[] = '✅ ' . explode(' ', $line)[0];
                } else {
                    $processes[] = '❌ ' . $line;
                }
            }
            $checks['supervisor'] = $processes;
        } else {
            $checks['supervisor'] = ['status' => '⚠️', 'message' => 'Cannot check supervisor'];
        }
        
        // Display results
        $this->table(['Check', 'Status', 'Details'], [
            ['Database', $checks['database']['status'], $checks['database']['message']],
            ['Disk Space', $checks['disk']['status'], "Used: {$checks['disk']['used']}, Free: {$checks['disk']['free']}"],
            ['Storage', $checks['storage']['storage'], 'Writable: ' . ($checks['storage']['storage'] === '✅' ? 'Yes' : 'No')],
            ['Queue', $checks['queue']['status'], $checks['queue']['message']],
            ['Memory', '✅', $checks['memory']['usage']],
        ]);
        
        // Log results
        \Log::info('Health check completed', [
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ]);
        
        // Determine overall status
        $hasErrors = collect($checks)->flatten()->contains('❌');
        
        if ($hasErrors) {
            $this->error('❌ System health check FAILED');
            return 1;
        }
        
        $this->info('✅ All health checks passed!');
        return 0;
    }
}