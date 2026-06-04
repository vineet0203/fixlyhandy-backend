<?php
// app/Models/TemporaryUpload.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TemporaryUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'temp_id',
        'user_id',
        'original_name',
        'storage_path',
        'disk',
        'mime_type',
        'size',
        'expires_at',
        'is_used',
        'final_path',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'is_used' => 'boolean',
        'size' => 'integer',
    ];

    /**
     * Scope for unused uploads
     */
    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    /**
     * Scope for expired uploads
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope for active uploads (not expired, not used)
     */
    public function scopeActive($query)
    {
        return $query->where('is_used', false)
            ->where('expires_at', '>', now());
    }

    /**
     * Get the user who uploaded the file
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}