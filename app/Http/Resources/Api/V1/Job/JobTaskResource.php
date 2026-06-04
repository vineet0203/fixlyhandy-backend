<?php
// app/Http/Resources/Api/V1/Job/JobTaskResource.php

namespace App\Http\Resources\Api\V1\Job;

use App\Http\Resources\Api\V1\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class JobTaskResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'completed' => (bool) $this->completed,
            'completed_at' => $this->completed_at?->format('M d, Y H:i'),
            'completed_by' => $this->whenLoaded('completedBy', function() {
                return new UserResource($this->completedBy);
            }),
            'due_date' => $this->due_date?->format('M d, Y'),
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->format('M d, Y H:i'),
            'updated_at' => $this->updated_at?->format('M d, Y H:i'),
        ];
    }
}