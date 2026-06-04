<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'item_name',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'item_total',
        'sort_order',
        'package_id',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'item_total' => 'decimal:2',
    ];

    /**
     * Relationship with quote
     */
    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Relationship with package
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Calculate item total
     */
    public function calculateTotal(): void
    {
        $subtotal = $this->unit_price * $this->quantity;
        $this->tax_amount = ($subtotal * $this->tax_rate) / 100;
       $this->item_total = $subtotal + $this->tax_amount;
    }
}
