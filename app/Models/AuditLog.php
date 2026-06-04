<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class AuditLog extends Model
{
    use Prunable;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'actor_type',
        'event',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'meta',
        'context',
        'company_id',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'meta'       => 'array',
    ];

    /**
     * Get the prunable model query.
     */
    public function prunable()
    {
        $retentionDays = config('audit.retention_days', 730);

        if ($retentionDays) {
            return static::where('created_at', '<=', now()->subDays($retentionDays));
        }

        return static::where('id', 0); // Never prune if retention_days is null
    }
}
