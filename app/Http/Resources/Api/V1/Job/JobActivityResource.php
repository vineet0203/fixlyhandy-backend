<?php
// app/Http/Resources/Api/V1/Job/JobActivityResource.php

namespace App\Http\Resources\Api\V1\Job;

use App\Http\Resources\Api\V1\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class JobActivityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'subject' => $this->subject,
            'content' => $this->content,
            'metadata' => $this->metadata,
            'performed_by' => new UserResource($this->whenLoaded('performedBy')),
            'created_at' => $this->created_at?->format('M d, Y H:i'),
            'created_at_diff' => $this->created_at?->diffForHumans(),
        ];
    }
}