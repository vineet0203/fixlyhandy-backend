<?php

namespace App\Http\Resources\Api\V1\ServiceCategory;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price !== null ? (float)$this->price : null,
            'icon' => $this->icon,
            'is_active' => (bool)$this->is_active,
            'sort_order' => (int)$this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
