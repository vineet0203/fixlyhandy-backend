<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'scope',
        'is_system_role',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_system_role' => 'boolean',
    ];

    public $timestamps = true;

    // Scopes
    public function scopePlatform($query)
    {
        return $query->where('scope', 'platform');
    }

    public function scopeCompany($query)
    {
        return $query->where('scope', 'company');
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system_role', true);
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot('assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    // Methods
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }
}