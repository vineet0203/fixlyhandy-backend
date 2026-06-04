<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeploymentReport extends Command
{
    protected $signature = 'deployments:report {--days=1 : Number of days to include}';
    protected $description = 'Generate deployment report';

    public function handle()
    {
        $days = $this->option('days');
        $startDate = now()->subDays($days);
        
        if (!DB::getSchemaBuilder()->hasTable('deployment_stats')) {
            $this->warn('Deployment stats table not found.');
            return 0;
        }
        
        $deployments = DB::table('deployment_stats')
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $total = $deployments->count();
        $successful = $deployments->where('success', true)->count();
        $failed = $total - $successful;
        $successRate = $total > 0 ? round(($successful / $total) * 100, 2) : 0;
        
        $this->info("📊 Deployment Report (Last {$days} day" . ($days > 1 ? 's' : '') . ")");
        $this->line(str_repeat('=', 50));
        
        $this->table(['Metric', 'Value'], [
            ['Total Deployments', $total],
            ['Successful', $successful],
            ['Failed', $failed],
            ['Success Rate', $successRate . '%'],
            ['Avg Duration', round($deployments->avg('duration_seconds'), 2) . 's'],
        ]);
        
        if ($deployments->isNotEmpty()) {
            $this->info("\n📅 Recent Deployments:");
            $headers = ['Time', 'Commit', 'Author', 'Duration', 'Status', 'Location'];
            $rows = [];
            
            foreach ($deployments->take(5) as $deploy) {
                $rows[] = [
                    $deploy->created_at->format('M d H:i'),
                    substr($deploy->commit_hash, 0, 8),
                    $deploy->author_name ?? 'Unknown',
                    $deploy->duration_seconds . 's',
                    $deploy->success ? '✅' : '❌',
                    $deploy->trigger_city ? "{$deploy->trigger_city}" : 'Unknown',
                ];
            }
            
            $this->table($headers, $rows);
        }
        
        // Send to Telegram if configured
        if (class_exists('App\Services\TelegramService')) {
            $telegram = new \App\Services\TelegramService();
            $message = "📊 *Deployment Report*\n\n";
            $message .= "Period: Last {$days} day" . ($days > 1 ? 's' : '') . "\n";
            $message .= "Total: {$total}\n";
            $message .= "Successful: {$successful}\n";
            $message .= "Failed: {$failed}\n";
            $message .= "Success Rate: {$successRate}%\n";
            
            $telegram->sendRawMessage($message);
        }
        
        \Log::info('Deployment report generated', [
            'days' => $days,
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $successRate,
        ]);
        
        return 0;
    }
}