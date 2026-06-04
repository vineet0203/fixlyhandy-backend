<?php
// app/Models/Job.php

namespace App\Models;

use App\Traits\HasSignedUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Job extends BaseModel
{
    use HasFactory, SoftDeletes, HasSignedUrl;

    protected $table = 'jobs';

    protected $fillable = [
        'job_number',
        'title',
        'description',
        'vendor_id',
        'client_id',
        'customer_id',
        'quote_id',
        'assigned_to',
        'created_by',
        'updated_by',
        'work_type',
        'priority',
        'status',
        'issue_date',
        'start_date',
        'end_date',
        'estimated_completion_date',
        'actual_completion_date',
        'currency',
        'estimated_amount',
        'total_amount',
        'deposit_amount',
        'paid_amount',
        'balance_due',
        'location_type',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'zip_code',
        'instructions',
        'notes',
        'is_converted_from_quote',
        'converted_at',
        'converted_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'estimated_completion_date' => 'date',
        'actual_completion_date' => 'datetime',
        'converted_at' => 'datetime',
        'estimated_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'is_converted_from_quote' => 'boolean',
    ];

    /**
     * Generate a unique work order number
     */
    public static function generateJobNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');

        $lastJob = self::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastJob) {
            $lastNumber = intval(substr($lastJob->job_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "WO-{$year}{$month}-{$newNumber}";
    }

    /**
     * Calculate totals from tasks or linked quote
     */
    public function calculateTotals(): void
    {
        // If linked to a quote, use quote totals
        if ($this->quote_id && $this->quote) {
            $this->total_amount = $this->quote->total_amount;
            $this->estimated_amount = $this->quote->total_amount;
            $this->balance_due = $this->total_amount - $this->paid_amount;
            $this->saveQuietly();
        }
    }

    /**
     * Update payment information
     */
    public function updatePayment(float $amount): void
    {
        $this->paid_amount += $amount;
        $this->balance_due = $this->total_amount - $this->paid_amount;
        $this->save();
    }

    /**
     * Check if work order can be started
     */
    public function canBeStarted(): bool
    {
        return in_array($this->status, ['pending', 'scheduled']) &&
            !$this->start_date?->isFuture();
    }

    /**
     * Check if work order can be completed
     */
    public function canBeCompleted(): bool
    {
        return in_array($this->status, ['in_progress', 'scheduled']) &&
            $this->tasks->where('completed', false)->count() === 0;
    }

    /**
     * Relationships
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function convertedBy()
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function tasks()
    {
        return $this->hasMany(JobTask::class)->orderBy('sort_order');
    }

    public function attachments()
    {
        return $this->hasMany(JobAttachment::class);
    }

    // Context-specific attachment relationships
    public function generalAttachments()
    {
        return $this->hasMany(JobAttachment::class)->where('context', JobAttachment::CONTEXT_GENERAL);
    }

    public function instructionAttachments()
    {
        return $this->hasMany(JobAttachment::class)->where('context', JobAttachment::CONTEXT_INSTRUCTIONS);
    }

    // Optional: Get all attachments for a specific section
    public function getSectionAttachments(string $section)
    {
        return $this->attachments()->where('context', $section)->get();
    }

    public function activities()
    {
        return $this->hasMany(JobActivity::class)->latest();
    }

    public function timeline()
    {
        return $this->hasMany(JobTimeline::class)->latest();
    }

    public function assignments()
    {
        return $this->hasMany(JobAssignment::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function dispatchSchedules()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

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
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', ['pending', 'scheduled'])
            ->where('start_date', '>=', now()->toDateString());
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['pending', 'scheduled', 'in_progress'])
            ->where('estimated_completion_date', '<', now()->toDateString());
    }

    public function invoiceItem()
    {
        return $this->hasOne(InvoiceItem::class, 'job_id');
    }
}
