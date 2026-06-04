<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSecurityLog extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'ip_address',
        'user_agent',
        'location',
        'metadata',
        'event_time',
        'created_by',  // Add for consistency
        'updated_by'   // Add for consistency
    ];

    protected $casts = [
        'metadata' => 'array',
        'event_time' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log security event
     */
    public static function logEvent(User $user, string $eventType, array $metadata = []): self
    {
        return static::create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'location' => self::getLocationFromIP(request()->ip()),
            'metadata' => $metadata,
            'event_time' => now()
        ]);
    }

    /**
     * Get recent failed login attempts
     */
    public static function getRecentFailedAttempts(User $user, int $minutes = 15): int
    {
        return static::where('user_id', $user->id)
            ->where('event_type', 'login_failed')
            ->where('event_time', '>=', now()->subMinutes($minutes))
            ->count();
    }

    private static function getLocationFromIP(?string $ip): ?string
    {
        if (!$ip || $ip === '127.0.0.1') {
            return 'Localhost';
        }

        // You can implement IP geolocation here
        // Example with a service like ipinfo.io
        // return file_get_contents("https://ipinfo.io/{$ip}/city");

        return null;
    }
}
