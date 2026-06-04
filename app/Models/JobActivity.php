<?php
// app/Models/JobActivity.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobActivity extends BaseModel
{
    use HasFactory;

    protected $table = 'job_activities';

    protected $fillable = [
        'job_id',
        'type',
        'subject',
        'content',
        'metadata',
        'performed_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * The valid types for activities
     */
    const TYPES = [
        'email', 'note', 'status_change', 'assignment',
        'completion', 'payment', 'attachment', 'other', 'created', 'updated', 'deleted'
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

    /**
     * Scopes
     */
    public function scopeEmails($query)
    {
        return $query->where('type', 'email');
    }

    public function scopeNotes($query)
    {
        return $query->where('type', 'note');
    }
}