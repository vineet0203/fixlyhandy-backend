<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends BaseModel
{
    use SoftDeletes;

    protected $table = 'schedules';

    protected $fillable = [
        'vendor_id',
        'job_id',
        'crew_id',
        'dispatch_crew_id',
        'employee_id',
        'title',
        'schedule_date',
        'start_time',
        'end_time',
        'start_datetime',
        'end_datetime',
        'priority',
        'status',
        'location_lat',
        'location_lng',
        'address',
        'notes',
        'is_multi_day',
        'is_recurring',
        'notify_client',
        'notify_crew',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'location_lat' => 'float',
        'location_lng' => 'float',
        'is_multi_day' => 'boolean',
        'is_recurring' => 'boolean',
        'notify_client' => 'boolean',
        'notify_crew' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function crew(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'crew_id');
    }

    public function dispatchCrew(): BelongsTo
    {
        return $this->belongsTo(Crew::class, 'dispatch_crew_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function jobAssignments()
    {
        return $this->hasMany(JobAssignment::class, 'job_id', 'job_id');
    }

    /**
     * Scopes
     */
    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->where('start_datetime', '>=', $startDate)
            ->where('end_datetime', '<=', $endDate);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_datetime', '>=', now())
            ->whereIn('status', ['scheduled', 'assigned', 'pending', 'in_progress'])
            ->orderBy('start_datetime');
    }

    protected function calendarStart(): Attribute
    {
        return Attribute::get(function () {
            if ($this->start_datetime) {
                return $this->start_datetime->toIso8601String();
            }

            if (!$this->schedule_date || !$this->start_time) {
                return null;
            }

            return $this->schedule_date->format('Y-m-d') . 'T' . substr((string) $this->start_time, 0, 8);
        });
    }

    protected function calendarEnd(): Attribute
    {
        return Attribute::get(function () {
            if ($this->end_datetime) {
                return $this->end_datetime->toIso8601String();
            }

            if (!$this->schedule_date || !$this->end_time) {
                return null;
            }

            return $this->schedule_date->format('Y-m-d') . 'T' . substr((string) $this->end_time, 0, 8);
        });
    }
}
