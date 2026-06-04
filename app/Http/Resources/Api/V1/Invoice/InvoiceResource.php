<?php

namespace App\Http\Resources\Api\V1\Invoice;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        $employee = $this->whenLoaded('employee');
        $items = $this->whenLoaded('items', fn () => $this->items, collect());
        $subtotal = (float) $items->sum('amount');
        $mileage = (float) $items->sum('mileage');
        $otherExpense = (float) $items->sum('other_expense');
        $grandTotal = (float) $items->sum('final_amount');

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'employee_id' => $this->employee_id,
            'bill_date' => $this->bill_date?->toDateString(),
            'delivery_date' => $this->delivery_date?->toDateString(),
            'payment_deadline' => $this->payment_deadline?->toDateString(),
            'mileage' => (float) $this->mileage,
            'other_expense' => (float) $this->other_expense,
            'note' => $this->note,
            'terms_conditions' => $this->terms_conditions,
            'billing_address' => $this->billing_address,
            'status' => $this->status,
            'customer_id' => $this->customer_id,
            'customer_status' => $this->customer_status,
            'reject_reason' => $this->reject_reason,
            'totals' => [
                'subtotal' => round($subtotal, 2),
                'mileage' => round($mileage, 2),
                'other_expense' => round($otherExpense, 2),
                'grand_total' => round($grandTotal, 2),
            ],
            'employee' => $employee ? [
                'id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'full_name' => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
                'email' => $employee->email,
                'mobile_number' => $employee->mobile_number,
                'address' => $employee->address,
                'profile_photo' => $employee->profile_photo_path,
            ] : null,
            'client' => $this->whenLoaded('client') ? [
                'id' => $this->client->id,
                'name' => $this->client->business_name ?? trim($this->client->first_name . ' ' . $this->client->last_name),
                'email' => $this->client->email,
                'phone' => $this->client->mobile_number,
                'address' => trim(implode(', ', array_filter([
                    $this->client->address_line_1,
                    $this->client->city,
                    $this->client->state,
                ]))),
            ] : null,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
