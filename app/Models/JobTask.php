<?php
// app/Models/JobTask.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobTask extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'job_tasks';

    protected $fillable = [
        'job_id',
        'name',
        'description',
        'completed',
        'completed_at',
        'completed_by',
        'due_date',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'due_date' => 'date',
    ];

    /**
     * Mark task as completed
     */
    public function markAsCompleted(int $userId): void
    {
        $this->completed = true;
        $this->completed_at = now();
        $this->completed_by = $userId;
        $this->save();
    }

    /**
     * Mark task as incomplete
     */
    public function markAsIncomplete(): void
    {
        $this->completed = false;
        $this->completed_at = null;
        $this->completed_by = null;
        $this->save();
    }

    /**
     * Relationships
     */
    public function Job()
    {
        return $this->belongsTo(Job::class);
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('completed', false);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', now()->toDateString());
    }

    public function scopeOverdue($query)
    {
        return $query->where('completed', false)
            ->where('due_date', '<', now()->toDateString());
    }
}