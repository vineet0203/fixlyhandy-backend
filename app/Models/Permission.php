<?php

namespace App\Models;

use Faker\Provider\Base;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
        'category',
        'scope',
        'created_by',
        'updated_by',
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

    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    // Relationships
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}