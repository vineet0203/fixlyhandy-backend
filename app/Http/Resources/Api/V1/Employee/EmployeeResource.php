<?php

namespace App\Http\Resources\Api\V1\Employee;

use App\Http\Resources\Api\V1\User\UserResource;
use App\Services\File\SignedUrlService;
use App\Traits\HasSignedUrl;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    use HasSignedUrl;

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'employee_id' => $this->employee_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'email' => $this->email,
            'mobile_number' => $this->mobile_number,
            'address' => $this->address,
            'designation' => $this->designation,
            'department' => $this->department,
            'reporting_manager' => $this->when($this->reportingManager, [
                'id' => $this->reportingManager?->id,
                'name' => $this->reportingManager?->full_name,
                'employee_id' => $this->reportingManager?->employee_id,
                'designation' => $this->reportingManager?->designation,
            ]),
            'role' => $this->role,
            'is_active' => $this->is_active,
            //'profile_photo' => $this->getSignedUrlData($this->profile_photo_path),

            // Metadata
            'created_by' => $this->whenLoaded('creator', function () {
                return new UserResource($this->creator);
            }),
            'updated_by' => $this->whenLoaded('updater', function () {
                return new UserResource($this->updater);
            }),
            'deleted_by' => $this->whenLoaded('deleter', function () {
                return new UserResource($this->deleter);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships count
            'subordinates_count' => $this->when(isset($this->subordinates_count), $this->subordinates_count),
        ];
    }
}
