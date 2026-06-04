<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RequestAnalyticsService;

class TestRequestAnalytics extends Command
{
    protected $signature = 'test:analytics {ip? : Optional IP address to test}';
    protected $description = 'Test the RequestAnalyticsService';

    public function handle()
    {
        $ip = $this->argument('ip');
        
        $this->info('Testing RequestAnalyticsService...');
        
        // Test 1: Check if service is registered
        try {
            $service = app(RequestAnalyticsService::class);
            $this->info('✅ Service registered successfully');
        } catch (\Exception $e) {
            $this->error('❌ Service not registered: ' . $e->getMessage());
            return 1;
        }
        
        // Test 2: Get analytics
        $analytics = $service->getAnalytics();
        $this->info('✅ Analytics fetched successfully');
        
        // Test 3: Display basic info
        $this->table(['Key', 'Value'], [
            ['IP Address', $analytics['ip']['ip_address']],
            ['Is Localhost', $analytics['ip']['is_localhost'] ? 'Yes' : 'No'],
            ['Country', $analytics['location']['country'] ?? 'Unknown'],
            ['City', $analytics['location']['city'] ?? 'Unknown'],
            ['Browser', $analytics['device']['browser'] ?? 'Unknown'],
            ['OS', $analytics['device']['os'] ?? 'Unknown'],
        ]);
        
        // Test 4: Test location string
        $location = $service->getLocationString();
        $this->info("📍 Location: {$location}");
        
        // Test 5: Test device string
        $device = $service->getDeviceString();
        $this->info("💻 Device: {$device}");
        
        $this->newLine();
        $this->info('🎉 RequestAnalyticsService is working correctly!');
        
        return 0;
    }
}