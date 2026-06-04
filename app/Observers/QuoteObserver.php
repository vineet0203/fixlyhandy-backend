<?php
// app/Observers/QuoteObserver.php

namespace App\Observers;

use App\Models\Quote;
use App\Services\Jobs\JobCreationService;
use Illuminate\Support\Facades\Log;

class QuoteObserver
{
    protected JobCreationService $jobService;

    public function __construct(JobCreationService $jobService)
    {
        $this->jobService = $jobService;
    }

    /**
     * Handle the Quote "updated" event.
     */
    public function updated(Quote $quote): void
    {
        try {
            $this->checkAndCreateJob($quote);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Observer failed silently", ["error" => $e->getMessage()]);
        }
    }

    /**
     * Handle the Quote "created" event (if a quote is created as accepted)
     */
    public function created(Quote $quote): void
    {
        $this->checkAndCreateJob($quote);
    }

    /**
     * Check if quote should be converted to work order and create it
     */
    protected function checkAndCreateJob(Quote $quote): void
    {
        // Check if quote was just accepted/approved
        $wasJustAccepted = $this->wasJustAccepted($quote);
        
        if (!$wasJustAccepted) {
            return;
        }

        // Check if quote can be converted
        if (!$quote->can_convert_to_job) {
            Log::info('Quote accepted but cannot be converted to job', [
                'quote_id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'reason' => 'can_convert_to_job is false'
            ]);
            return;
        }

        // Check if already converted
        if ($quote->is_converted) {
            Log::info('Quote accepted but already converted', [
                'quote_id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'existing_job_id' => $quote->job_id ?? $quote->job_id
            ]);
            return;
        }

        // Log the automatic conversion attempt
        Log::info('Auto-converting accepted quote to work order', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number,
            'approval_status' => $quote->approval_status,
            'status' => $quote->status,
            'triggered_by' => 'observer'
        ]);

        // Skip auto-convert if no vendor_id (customer context)
        if (!$quote->vendor_id) {
            return;
        }

        try {
            // Create work order from quote
            $job = $this->jobService->convertFromQuote(
                $quote->id,
                $quote->updated_by ?? $quote->created_by ?? 1 // Fallback to system user
            );

            Log::info('Successfully auto-converted quote to work order', [
                'quote_id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'job_id' => $job->id,
                'job_number' => $job->job_number
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to auto-convert quote to work order', [
                'quote_id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // You could dispatch a job here to retry later
            // dispatch(new ConvertQuoteToJob($quote->id))->delay(now()->addMinutes(5));
        }
    }

    /**
     * Determine if the quote was just accepted
     */
    protected function wasJustAccepted(Quote $quote): bool
    {
        // If it's a new quote created as accepted
        if (!$quote->exists) {
            return in_array($quote->approval_status, ['accepted', 'approved']) || 
                   in_array($quote->status, ['accepted', 'approved']);
        }

        // Check if the model was recently changed
        if (!$quote->wasChanged()) {
            return false;
        }

        // Check if approval_status was changed to accepted
        if ($quote->wasChanged('approval_status') && 
            in_array($quote->approval_status, ['accepted', 'approved'])) {
            return true;
        }

        // Check if status was changed to accepted
        if ($quote->wasChanged('status') && 
            in_array($quote->status, ['accepted', 'approved'])) {
            return true;
        }

        return false;
    }
}