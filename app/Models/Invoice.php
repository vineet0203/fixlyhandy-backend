<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Invoice extends Model
{
    use HasFactory;
    protected $table = 'invoices';
    protected $fillable = [
        'invoice_number',
        'employee_id',
        'client_id',
        'bill_date',
        'delivery_date',
        'payment_deadline',
        'mileage',
        'other_expense',
        'note',
        'terms_conditions',
        'billing_address',
        'status',
        'customer_id',
        'customer_status',
        'reject_reason',
    ];
    protected $casts = [
        'bill_date' => 'date',
        'delivery_date' => 'date',
        'payment_deadline' => 'date',
        'mileage' => 'decimal:2',
        'other_expense' => 'decimal:2',
        'billing_address' => 'array',
    ];
    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
        });
    }
    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $prefix = "#{$year}-{$month}-";
        $last = static::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderByDesc('id')
            ->first();
        $next = 1;
        if ($last && !empty($last->invoice_number)) {
            $parts = explode('-', str_replace('#', '', $last->invoice_number));
            $serial = isset($parts[2]) ? (int) $parts[2] : 0;
            $next = $serial + 1;
        }
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
    public function publicLinks(): HasMany
    {
        return $this->hasMany(InvoicePublicLink::class);
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
