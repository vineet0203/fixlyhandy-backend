<?php
// app/Http/Middleware/CheckUploadSize.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUploadSize
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check all requests with potential payload
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
            $maxAllowedSize = 5 * 1024 * 1024; // 5MB

            // Skip if content length is 0 or not set (some requests might not have it)
            if ($contentLength > 0 && $contentLength > $maxAllowedSize) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request payload exceeds maximum allowed limit of 5MB.',
                    'error_code' => 'PAYLOAD_TOO_LARGE',
                    'details' => [
                        'max_allowed' => '5MB',
                        'your_payload_size' => $this->formatBytes($contentLength),
                    ],
                    'timestamp' => now()->toISOString(),
                ], 413);
            }
        }

        return $next($request);
    }

    /**
     * Format bytes to human-readable
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);

        if ($bytes === 0) {
            return '0 B';
        }

        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
