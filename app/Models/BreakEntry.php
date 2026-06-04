<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'time_entry_id',
        'break_start',
        'break_end',
        'break_duration',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'break_duration' => 'integer',
    ];

    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class);
    }
}
