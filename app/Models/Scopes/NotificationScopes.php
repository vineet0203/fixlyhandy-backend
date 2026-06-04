<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait NotificationScopes
{
    public function scopeUnread(Builder $query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead(Builder $query)
    {
        return $query->where('is_read', true);
    }
}