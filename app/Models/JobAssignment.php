<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAssignment extends BaseModel
{
    protected $table = 'job_assignments';

    protected $fillable = [
        'job_id',
        'employee_id',
        'shift',
        'assigned_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
