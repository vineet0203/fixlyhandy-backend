<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use Illuminate\Support\Facades\Log;

class QuoteDeletionService
{
    /**
     * Check if quote can be deleted
     */
    public function canDelete(Quote $quote): array
    {
        if ($quote->status === 'approved') {
            return [
                'can_delete' => false,
                'message' => 'Approved quotes cannot be deleted.',
            ];
        }
        
        return [
            'can_delete' => true,
            'message' => '',
        ];
    }
    
    /**
     * Soft delete a quote
     */
    public function softDelete(Quote $quote, int $deletedBy): void
    {
        $quote->update(['updated_by' => $deletedBy]);
        $quote->delete();
        
        Log::info('Quote soft deleted', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number,
            'deleted_by' => $deletedBy,
        ]);
    }
    
    /**
     * Permanently delete a quote
     */
    public function forceDelete(Quote $quote): void
    {
        $quoteNumber = $quote->quote_number;
        $quote->forceDelete();
        
        Log::warning('Quote permanently deleted', [
            'quote_number' => $quoteNumber,
        ]);
    }
    
    /**
     * Restore a soft deleted quote
     */
    public function restore(int $quoteId, int $restoredBy): ?Quote
    {
        $quote = Quote::withTrashed()->find($quoteId);
        
        if (!$quote) {
            return null;
        }
        
        $quote->restore();
        $quote->update(['updated_by' => $restoredBy]);
        
        Log::info('Quote restored', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number,
            'restored_by' => $restoredBy,
        ]);
        
        return $quote->fresh();
    }
}