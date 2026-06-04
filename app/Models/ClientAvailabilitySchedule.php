<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClientAvailabilitySchedule extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'client_availability_schedules';

    protected $fillable = [
        'client_id',
        'available_days',
        'preferred_start_time',
        'preferred_end_time',
        'has_lunch_break',
        'lunch_start',
        'lunch_end',
        'notes',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'available_days' => 'array',
        'has_lunch_break' => 'boolean',
        'is_active' => 'boolean',
        'preferred_start_time' => 'string',
        'preferred_end_time' => 'string',
        'lunch_start' => 'string',
        'lunch_end' => 'string',
    ];

    /**
     * Relationships
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    // Updated scopes - removed date-based scopes since columns don't exist
    public function scopeCurrent($query)
    {
        // Since we don't have dates, just return active schedules
        return $query->where('is_active', true);
    }

    public function scopeUpcoming($query)
    {
        // Without dates, we can't determine upcoming vs current
        // Return empty or handle differently based on your business logic
        return $query->whereRaw('1 = 0'); // Returns empty collection
    }

    public function scopeExpired($query)
    {
        // Without dates, nothing is expired
        return $query->whereRaw('1 = 0'); // Returns empty collection
    }

    /**
     * Accessors & Mutators
     */
    public function getAvailableDaysListAttribute(): string
    {
        if (empty($this->available_days)) {
            return '';
        }

        $days = array_map('ucfirst', (array) $this->available_days);
        return implode(', ', $days);
    }

    public function getDaysOfWeekAttribute(): array
    {
        return (array) $this->available_days;
    }

    public function getTimeSlotDisplayAttribute(): ?string
    {
        if (!$this->preferred_start_time || !$this->preferred_end_time) {
            return null;
        }

        try {
            $start = Carbon::createFromFormat('H:i:s', $this->preferred_start_time . ':00')
                ->format('g:i A');
            $end = Carbon::createFromFormat('H:i:s', $this->preferred_end_time . ':00')
                ->format('g:i A');
            return "{$start} - {$end}";
        } catch (\Exception $e) {
            try {
                $start = Carbon::createFromFormat('H:i', $this->preferred_start_time)
                    ->format('g:i A');
                $end = Carbon::createFromFormat('H:i', $this->preferred_end_time)
                    ->format('g:i A');
                return "{$start} - {$end}";
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    public function getLunchBreakDisplayAttribute(): ?string
    {
        if (!$this->has_lunch_break || !$this->lunch_start || !$this->lunch_end) {
            return null;
        }

        try {
            $start = Carbon::createFromFormat('H:i:s', $this->lunch_start . ':00')
                ->format('g:i A');
            $end = Carbon::createFromFormat('H:i:s', $this->lunch_end . ':00')
                ->format('g:i A');
            return "{$start} - {$end}";
        } catch (\Exception $e) {
            try {
                $start = Carbon::createFromFormat('H:i', $this->lunch_start)
                    ->format('g:i A');
                $end = Carbon::createFromFormat('H:i', $this->lunch_end)
                    ->format('g:i A');
                return "{$start} - {$end}";
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    // Removed scheduleValidityDisplay attribute since dates don't exist

    public function getDurationAttribute(): ?string
    {
        if (!$this->preferred_start_time || !$this->preferred_end_time) {
            return null;
        }

        try {
            $start = Carbon::parse($this->preferred_start_time);
            $end = Carbon::parse($this->preferred_end_time);

            $hours = $end->diffInHours($start);
            $minutes = $end->diffInMinutes($start) % 60;

            if ($minutes > 0) {
                return "{$hours}h {$minutes}m";
            }

            return "{$hours}h";
        } catch (\Exception $e) {
            return null;
        }
    }

    // Updated isCurrent attribute - without dates, just check if active
    public function getIsCurrentAttribute(): bool
    {
        return $this->is_active;
    }

    /**
     * Business Logic Methods
     */
    public function isAvailableOnDay(string $day): bool
    {
        $day = strtolower($day);
        return in_array($day, (array) $this->available_days);
    }

    // Removed isWithinSchedule method since dates don't exist

    public function isWithinTimeSlot(string $time): bool
    {
        try {
            $checkTime = Carbon::parse($time);
            $startTime = Carbon::parse($this->preferred_start_time);
            $endTime = Carbon::parse($this->preferred_end_time);

            return $checkTime->between($startTime, $endTime);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isLunchTime(string $time): bool
    {
        if (!$this->has_lunch_break || !$this->lunch_start || !$this->lunch_end) {
            return false;
        }

        try {
            $checkTime = Carbon::parse($time);
            $lunchStart = Carbon::parse($this->lunch_start);
            $lunchEnd = Carbon::parse($this->lunch_end);

            return $checkTime->between($lunchStart, $lunchEnd);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate next available dates
     */
    public function getNextAvailableDates(int $count = 5): array
    {
        $dates = [];
        $current = now();

        for ($i = 0; $i < 30 && count($dates) < $count; $i++) {
            $checkDate = $current->copy()->addDays($i);
            $dayOfWeek = strtolower($checkDate->englishDayOfWeek);

            if ($this->isAvailableOnDay($dayOfWeek)) {
                $dates[] = $checkDate->toDateString();
            }
        }

        return $dates;
    }

    // Updated schedule status without dates
    public function getScheduleStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        return 'active';
    }

    /**
     * Check if time slot is available (considering lunch break)
     */
    public function isTimeSlotAvailable(string $startTime, string $endTime): bool
    {
        try {
            $start = Carbon::parse($startTime);
            $end = Carbon::parse($endTime);

            // Check if within preferred hours
            if (!$this->isWithinTimeSlot($startTime) || !$this->isWithinTimeSlot($endTime)) {
                return false;
            }

            // Check lunch break overlap
            if ($this->has_lunch_break && $this->lunch_start && $this->lunch_end) {
                $lunchStart = Carbon::parse($this->lunch_start);
                $lunchEnd = Carbon::parse($this->lunch_end);

                if ($start->lt($lunchEnd) && $end->gt($lunchStart)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}