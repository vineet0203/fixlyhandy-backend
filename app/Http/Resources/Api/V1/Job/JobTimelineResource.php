<?php
// app/Http/Resources/Api/V1/Job/JobTimelineResource.php

namespace App\Http\Resources\Api\V1\Job;

use App\Http\Resources\Api\V1\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class JobTimelineResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'description' => $this->description,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'performed_by' => new UserResource($this->whenLoaded('performedBy')),
            'created_at' => $this->created_at?->format('M d, Y H:i'),
            'created_at_diff' => $this->created_at?->diffForHumans(),
        ];
    }
}