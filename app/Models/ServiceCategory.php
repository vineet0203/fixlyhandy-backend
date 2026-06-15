<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
