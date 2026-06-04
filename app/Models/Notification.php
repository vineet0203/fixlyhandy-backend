<?php

namespace App\Models;

use App\Models\Relations\NotificationRelations;
use App\Models\Scopes\NotificationScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Notification extends BaseModel
{
    use HasFactory, NotificationRelations, NotificationScopes;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Methods
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}