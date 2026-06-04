<?php

namespace App\Models\Relations;

use App\Models\User;

trait NotificationRelations
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}