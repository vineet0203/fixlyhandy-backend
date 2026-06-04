<?php

namespace App\Models\Relations;

use App\Models\Role;

trait PermissionRelations
{
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}

