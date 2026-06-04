<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'job_id',
        'job_name',
        'mileage',
        'other_expense',
        'amount',
        'final_amount',
    ];

    protected $casts = [
        'mileage' => 'decimal:2',
        'other_expense' => 'decimal:2',
        'amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
