<?php

namespace App\Traits;

use App\Services\RequestAnalyticsService;

trait TracksRequestAnalytics
{
    protected function getRequestAnalytics(): array
    {
        $analytics = app(RequestAnalyticsService::class);
        return $analytics->getAnalytics();
    }
    
    protected function getRequestSummary(): array
    {
        $analytics = app(RequestAnalyticsService::class);
        return $analytics->getSummary();
    }
    
    protected function logRequestAnalytics(string $event, array $context = []): void
    {
        $analytics = app(RequestAnalyticsService::class);
        $data = array_merge([
            'event' => $event,
            'analytics' => $analytics->getSummary(),
        ], $context);
        
        \Log::channel('analytics')->info($event, $data);
    }
}