<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'category',
        'sub_category_id',
        'sub_category',
        'price',
        'location',
        'detailed_address',
        'latitude',
        'longitude',
        'status',
        'featured',
        'sort_order',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'sort_order' => 'integer',
        'sub_category_id' => 'integer',
    ];
}
