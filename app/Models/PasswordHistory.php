<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordHistory extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'password_hash',
        'changed_at',
        'ip_address',
        'user_agent',
        'changed_by',
        'created_by',  // Add for consistency
        'updated_by'   // Add for consistency
    ];

    protected $casts = [
        'changed_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Check if password hash exists in user's history
     */
    public static function isPasswordInHistory(User $user, string $passwordHash): bool
    {
        return static::where('user_id', $user->id)
            ->where('password_hash', $passwordHash)
            ->exists();
    }

    /**
     * Get user's password history (limited to configured size)
     */
    public static function getUserHistory(User $user, ?int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('user_id', $user->id)
            ->orderBy('changed_at', 'desc');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }
}