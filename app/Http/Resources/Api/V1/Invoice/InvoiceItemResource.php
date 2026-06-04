<?php

namespace App\Http\Resources\Api\V1\Invoice;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'job_id' => $this->job_id,
            'job_name' => $this->job_name,
            'mileage' => (float) $this->mileage,
            'other_expense' => (float) $this->other_expense,
            'amount' => (float) $this->amount,
            'final_amount' => (float) $this->final_amount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
