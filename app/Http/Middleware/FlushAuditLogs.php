<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditLogService;
use Closure;

class FlushAuditLogs
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Flush any batched audit logs after the request is complete
        AuditLogService::flushBatch();

        return $response;
    }
}
