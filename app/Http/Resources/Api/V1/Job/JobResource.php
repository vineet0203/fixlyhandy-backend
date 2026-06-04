<?php
// app/Http/Resources/Api/V1/Job/JobResource.php

namespace App\Http\Resources\Api\V1\Job;

use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\JobAttachment;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray($request)
    {
        // Get all attachments
        $allAttachments = $this->whenLoaded('attachments', function () {
            return JobAttachmentResource::collection($this->attachments);
        }, []);

        // Split attachments by context
        $generalAttachments = [];
        $instructionAttachments = [];

        if ($this->relationLoaded('attachments') && $this->attachments) {
            foreach ($this->attachments as $attachment) {
                if ($attachment->context === JobAttachment::CONTEXT_GENERAL) {
                    $generalAttachments[] = $attachment;
                } elseif ($attachment->context === JobAttachment::CONTEXT_INSTRUCTIONS) {
                    $instructionAttachments[] = $attachment;
                }
            }
        }


        return [
            'id' => $this->id,
            'job_number' => $this->job_number,
            'title' => $this->title,
            'description' => $this->description,

            // Relationships - Check if loaded before using
            'client' => $this->whenLoaded('client', function () {
                return new JobClientResource($this->client);
            }),
            'quote' => $this->whenLoaded('quote', function () {
                return new JobQuoteResource($this->quote);
            }),
            'assigned_to' => $this->whenLoaded('assignedTo', function () {
                return new UserResource($this->assignedTo);
            }),
            'latest_assignment' => $this->whenLoaded('assignments', function () {
                $latest = $this->assignments->sortByDesc('created_at')->first();
                if ($latest && $latest->employee) {
                    return [
                        'id' => $latest->id,
                        'employee_id' => $latest->employee->id,
                        'employee_name' => trim($latest->employee->first_name . ' ' . $latest->employee->last_name),
                        'shift' => $latest->shift,
                        'assigned_at' => $latest->assigned_at?->format('M d, Y H:i'),
                    ];
                }
                return null;
            }),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return new UserResource($this->createdBy);
            }),
            'updated_by' => $this->whenLoaded('updatedBy', function () {
                return new UserResource($this->updatedBy);
            }),

            // Work order details
            'work_type' => $this->work_type,
            'priority' => $this->priority,
            'status' => $this->status,

            // Dates
            'issue_date' => $this->issue_date?->format('M d, Y'),
            'start_date' => $this->start_date?->format('M d, Y'),
            'end_date' => $this->end_date?->format('M d, Y'),
            'estimated_completion_date' => $this->estimated_completion_date?->format('M d, Y'),
            'actual_completion_date' => $this->actual_completion_date?->format('M d, Y H:i'),

            // Financial
            'currency' => $this->currency,
            'estimated_amount' => (float) $this->estimated_amount,
            'total_amount' => (float) $this->total_amount,
            'deposit_amount' => (float) $this->deposit_amount,
            'paid_amount' => (float) $this->paid_amount,
            'balance_due' => (float) $this->balance_due,
            'formatted_total' => $this->currency . ' ' . number_format((float) $this->total_amount, 2),
            'formatted_balance' => $this->currency . ' ' . number_format((float) $this->balance_due, 2),

            // Location
            // 'location_type' => $this->location_type,
            // 'address' => [
            //     'address_line_1' => $this->address_line_1,
            //     'address_line_2' => $this->address_line_2,
            //     'city' => $this->city,
            //     'state' => $this->state,
            //     'country' => $this->country,
            //     'zip_code' => $this->zip_code,
            //     'full_address' => $this->getFullAddressAttribute(),
            // ],

            // Additional info
            'instructions' => $this->instructions,
            'notes' => $this->notes,

            // Conversion tracking
            'is_converted_from_quote' => (bool) $this->is_converted_from_quote,
            'converted_at' => $this->converted_at?->format('M d, Y H:i'),

            // Collections - Use whenLoaded with fallback to empty collection
            'tasks' => $this->whenLoaded('tasks', function () {
                return JobTaskResource::collection($this->tasks);
            }, []),
            'attachments_by_context' => [
                'general' => JobAttachmentResource::collection($generalAttachments),
                'instructions' => JobAttachmentResource::collection($instructionAttachments),
            ],
            // 'activities' => $this->whenLoaded('activities', function () {
            //     return JobActivityResource::collection($this->activities);
            // }, []),

            // Stats - Calculate safely
            'stats' => [
                'total_tasks' => $this->whenLoaded('tasks', function () {
                    return $this->tasks->count();
                }, 0),
                'completed_tasks' => $this->whenLoaded('tasks', function () {
                    return $this->tasks->where('completed', true)->count();
                }, 0),
                'pending_tasks' => $this->whenLoaded('tasks', function () {
                    return $this->tasks->where('completed', false)->count();
                }, 0),
                'total_attachments' => count($generalAttachments) + count($instructionAttachments),
                'general_attachments' => count($generalAttachments),
                'instruction_attachments' => count($instructionAttachments),
            ],

            // Timestamps
            'is_invoiced' => \Illuminate\Support\Facades\DB::table('invoice_items')
                ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->where('invoice_items.job_id', $this->id)
                ->exists(),
            'created_at' => $this->created_at?->format('M d, Y H:i'),
            'updated_at' => $this->updated_at?->format('M d, Y H:i'),
            'deleted_at' => $this->deleted_at?->format('M d, Y H:i'),
        ];
    }

    // protected function getFullAddressAttribute(): ?string
    // {
    //     $parts = array_filter([
    //         $this->address_line_1,
    //         $this->address_line_2,
    //         $this->city,
    //         $this->state,
    //         $this->zip_code,
    //         $this->country,
    //     ]);

    //     return empty($parts) ? null : implode(', ', $parts);
    // }
}
