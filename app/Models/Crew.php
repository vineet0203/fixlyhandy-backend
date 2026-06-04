<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crew extends Model
{
    use HasFactory;

    protected $table = 'crews';

    protected $fillable = [
        'name',
        'description',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(CrewMember::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'dispatch_crew_id');
    }
}
