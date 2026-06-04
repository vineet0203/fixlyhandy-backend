<?php

namespace App\Http\Resources\Api\V1\Quote;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteReminderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'follow_up_schedule' => $this->scheduled_at->format('Y-m-d H:i:s'),
            'reminder_type' => $this->reminder_type,
            'reminder_status' => $this->status,
            'sent_at' => $this->sent_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}