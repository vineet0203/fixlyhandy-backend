<?php
// app/Models/JobTimeline.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobTimeline extends BaseModel
{
    use HasFactory;

    protected $table = 'job_timeline';

    protected $fillable = [
        'job_id',
        'event_type',
        'description',
        'old_values',
        'new_values',
        'performed_by',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Relationships
     */
    public function Job()
    {
        return $this->belongsTo(Job::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}