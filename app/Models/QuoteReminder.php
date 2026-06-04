<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteReminder extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'quote_id',
        'scheduled_at',
        'reminder_type',
        'status',
        'sent_at',
        'response',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeDue($query)
    {
        return $query->where('scheduled_at', '<=', now())
            ->where('status', 'scheduled');
    }
}