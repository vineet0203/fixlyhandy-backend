<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class AuditLogService
{
    private static array $batchLogs = [];

    /**
     * Global audit logger
     */
    public static function log(
        string $event,
        string $entityType,
        ?int $entityId = null,
        array $oldValues = null,
        array $newValues = null,
        array $meta = [],
        ?int $companyId = null,
        ?int $userId = null,
        string $actorType = 'user',
        ?string $context = null
    ): void {
        try {
            // Check if audit logging is enabled
            if (!config('audit.enabled', false)) {
                return;
            }

            // Filter sensitive data
            $filteredOldValues = self::filterSensitiveData($oldValues);
            $filteredNewValues = self::filterSensitiveData($newValues);
            $filteredMeta = self::filterSensitiveData($meta);

            $logData = [
                'user_id'     => $userId ?? Auth::id(),
                'actor_type'  => $actorType,
                'event'       => $event,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'old_values'  => $filteredOldValues,
                'new_values'  => $filteredNewValues,
                'meta'        => self::buildMeta($filteredMeta),
                'company_id'  => $companyId,
                'context'     => $context,
                'ip_address'  => request()?->ip(),
                'created_at'  => now(),
            ];

            // Use batch logging for high-frequency events
            if (self::isHighFrequencyEvent($event)) {
                self::addToBatch($logData);
                return;
            }

            // Regular sync logging
            AuditLog::create($logData);
        } catch (\Throwable $e) {
            // Audit logs must NEVER break business flow
            Log::error('Audit log failed', [
                'error' => $e->getMessage(),
                'event' => $event,
                'entity' => $entityType,
                'entity_id' => $entityId,
            ]);
        }
    }

    /**
     * Filter sensitive data before logging
     */
    protected static function filterSensitiveData(?array $data): ?array
    {
        if (!$data) {
            return $data;
        }

        $sensitiveFields = config('audit.sensitive_fields', [
            'password',
            'token',
            'secret',
            'key',
            'ssn',
            'credit_card',
            'bank_account',
            'iban',
            'cvv',
            'passport',
            'sin'
        ]);

        $filtered = [];
        foreach ($data as $key => $value) {
            if (self::isSensitiveField($key, $sensitiveFields)) {
                $filtered[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $filtered[$key] = self::filterSensitiveData($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Check if field contains sensitive information
     */
    private static function isSensitiveField(string $field, array $sensitiveFields): bool
    {
        $field = strtolower($field);

        foreach ($sensitiveFields as $sensitive) {
            if (str_contains($field, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add log to batch for bulk insert
     */
    private static function addToBatch(array $logData): void
    {
        // Check if audit logging is enabled before adding to batch
        if (!config('audit.enabled', false)) {
            return;
        }

        self::$batchLogs[] = $logData;

        // Flush batch if it reaches a certain size
        if (count(self::$batchLogs) >= config('audit.batch_size', 100)) {
            self::flushBatch();
        }
    }

    /**
     * Flush batched logs to database
     */
    public static function flushBatch(): void
    {
        // Check if audit logging is enabled before flushing
        if (!config('audit.enabled', false)) {
            self::$batchLogs = []; // Clear batch anyway
            return;
        }

        if (!empty(self::$batchLogs)) {
            try {
                AuditLog::insert(self::$batchLogs);
                self::$batchLogs = [];
            } catch (\Throwable $e) {
                Log::error('Audit log batch insert failed', [
                    'error' => $e->getMessage(),
                    'count' => count(self::$batchLogs),
                ]);
            }
        }
    }

    /**
     * Determine if event is high frequency
     */
    private static function isHighFrequencyEvent(string $event): bool
    {
        $highFrequencyEvents = config('audit.high_frequency_events', [
            'login',
            'page_view',
            'api_request',
            'search'
        ]);

        return in_array($event, $highFrequencyEvents);
    }

    /**
     * Merge safe metadata
     */
    protected static function buildMeta(array $meta): array
    {
        return array_filter([
            ...$meta,
            'request_id' => request()?->header('X-Request-Id'),
            'user_agent' => request()?->userAgent(),
            'url'        => request()?->fullUrl(),
        ]);
    }

    /**
     * Convenience method for logging CRUD operations
     */
    public static function logCrud(
        string $action, // 'created', 'updated', 'deleted', 'restored'
        string $entityType,
        $entity,
        ?array $changes = null,
        array $meta = [],
        ?int $userId = null
    ): void {
        // Early return if audit logging is disabled
        if (!config('audit.enabled', false)) {
            return;
        }

        $oldValues = null;
        $newValues = null;

        if ($action === 'updated' && $changes) {
            $oldValues = $changes['old'] ?? null;
            $newValues = $changes['new'] ?? null;
        }

        self::log(
            event: $action,
            entityType: $entityType,
            entityId: $entity->id ?? null,
            oldValues: $oldValues,
            newValues: $newValues,
            meta: $meta,
            companyId: $entity->company_id ?? null,
            userId: $userId,
            context: 'crud_operation'
        );
    }

    /**
     * Log authentication events
     */
    public static function logAuth(string $event, ?User $user = null, array $meta = []): void
    {
        // Early return if audit logging is disabled
        if (!config('audit.enabled', false)) {
            return;
        }

        self::log(
            event: $event,
            entityType: 'user',
            entityId: $user?->id,
            meta: array_merge($meta, [
                'user_email' => $user?->email,
                'auth_method' => $meta['auth_method'] ?? 'unknown',
            ]),
            companyId: $user?->company_id,
            userId: $user?->id,
            context: 'authentication'
        );
    }

    /**
     * Log API requests
     */
    public static function logApiRequest(string $method, string $endpoint, int $statusCode, array $meta = []): void
    {
        // Early return if audit logging is disabled
        if (!config('audit.enabled', false)) {
            return;
        }

        self::log(
            event: 'api_request',
            entityType: 'api',
            oldValues: null,
            newValues: null,
            meta: array_merge($meta, [
                'method' => $method,
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'response_time' => $meta['response_time'] ?? null,
            ]),
            context: 'api'
        );
    }

    /**
     * Check if audit logging is enabled
     */
    public static function isEnabled(): bool
    {
        return config('audit.enabled', false);
    }
}
