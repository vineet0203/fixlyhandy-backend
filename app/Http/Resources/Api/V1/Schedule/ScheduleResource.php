<?php

namespace App\Http\Resources\Api\V1\Schedule;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'crew_id' => $this->crew_id,
            'start_datetime' => $this->start_datetime?->toIso8601String(),
            'end_datetime' => $this->end_datetime?->toIso8601String(),
            'priority' => $this->priority,
            'status' => $this->status,
            'notes' => $this->notes,
            'is_multi_day' => $this->is_multi_day,
            'is_recurring' => $this->is_recurring,
            'notify_client' => $this->notify_client,
            'notify_crew' => $this->notify_crew,

            // Relationships
            'job' => $this->whenLoaded('job', function () {
                return [
                    'id' => $this->job->id,
                    'job_number' => $this->job->job_number,
                    'title' => $this->job->title,
                    'status' => $this->job->status,
                    'client' => $this->when($this->job->relationLoaded('client'), function () {
                        return [
                            'id' => $this->job->client->id,
                            'name' => $this->job->client->company_name ?? ($this->job->client->first_name . ' ' . $this->job->client->last_name),
                        ];
                    }),
                    'address_line_1' => $this->job->address_line_1,
                    'city' => $this->job->city,
                    'state' => $this->job->state,
                    'work_type' => $this->job->work_type,
                ];
            }),

            'crew' => $this->whenLoaded('crew', function () {
                return [
                    'id' => $this->crew->id,
                    'name' => trim($this->crew->first_name . ' ' . $this->crew->last_name),
                    'employee_id' => $this->crew->employee_id,
                    'designation' => $this->crew->designation,
                ];
            }),

            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
