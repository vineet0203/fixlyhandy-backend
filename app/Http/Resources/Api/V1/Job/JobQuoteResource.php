<?php
// app/Http/Resources/Api/V1/Job/JobQuoteResource.php

namespace App\Http\Resources\Api\V1\Job;

use Illuminate\Http\Resources\Json\JsonResource;

class JobQuoteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'quote_number' => $this->quote_number,
            'title' => $this->title,
            'total_amount' => (float) $this->total_amount,
            'formatted_total' => $this->currency . ' ' . number_format((float) $this->total_amount, 2),
            'converted_at' => $this->converted_at?->format('M d, Y'),
        ];
    }
}
