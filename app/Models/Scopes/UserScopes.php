<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait UserScopes
{
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopePlatformAdmins(Builder $query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('scope', 'platform')->where('slug', 'platform_super_admin');
        });
    }
}