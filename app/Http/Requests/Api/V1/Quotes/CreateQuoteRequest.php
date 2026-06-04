<?php

namespace App\Http\Requests\Api\V1\Quotes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Section 1: Quote Details
            'title' => 'required|string|max:255',
            'quote_due_date' => 'sometimes|nullable|date',
            'client_id' => 'required|exists:clients,id',
            'customer_id' => 'sometimes|exists:clients,id',
            'status' => 'sometimes|in:draft,sent,pending,accepted,rejected,expired',
            'equity_status' => 'sometimes|in:pending,approved,rejected,not_applicable',
            'currency' => 'required|string|size:3|in:USD,EUR,GBP,JPY,CAD,AUD',

            // Section 2: Line Items
            'line_items' => 'required|array|min:1',
            'line_items.*.item_name' => 'required|string|max:255',
            'line_items.*.description' => 'nullable|string|max:255',
            'line_items.*.quantity' => 'required|integer|min:1',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'line_items.*.tax_rate' => 'nullable|numeric|between:0,100',
            'line_items.*.package_id' => 'nullable|exists:packages,id',

            // Section 3: Pricing Summary
            'discount' => 'nullable|numeric|min:0',
            'is_tax_applicable' => 'sometimes|boolean',
            'tax_percentage' => 'required_if:is_tax_applicable,true|integer|in:0,5,12,18,28',
            'deposit_required' => 'sometimes|boolean',
            'deposit_type' => 'nullable|in:none,percentage,fixed',
            'deposit_amount' => 'required_if:deposit_required,true|numeric|min:0',

            // Section 4: Client Approval
            'approval_status' => 'sometimes|in:pending,accepted,rejected',
            'client_signature' => 'nullable|string',
            'approval_date' => 'nullable|date',
            'approval_action_date' => 'nullable|date',

            // Section 5: Follow Ups & Reminders
            'reminders' => 'sometimes|array',
            'reminders.*.follow_up_schedule' => 'required_with:reminders|date',
            'reminders.*.reminder_type' => 'required_with:reminders|in:email,sms,notification',
            'reminders.*.reminder_status' => 'sometimes|in:scheduled,sent,cancelled',

            // Section 6: Conversion to Job
            'can_convert_to_job' => 'sometimes|boolean',

            // Meta
            'notes' => 'nullable|string',
            'expires_at' => 'nullable|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Quote title is required.',
            'title.max' => 'Title must be at most 255 characters.',
            'client_id.required' => 'Client is required.',
            'client_id.exists' => 'Selected client does not exist.',
            'status.in' => 'Invalid quote status selected.',
            'equity_status.in' => 'Invalid equity status selected.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be 3 characters.',
            'currency.in' => 'Invalid currency selected.',
            'line_items.required' => 'At least one line item is required.',
            'line_items.min' => 'At least one line item is required.',
            'line_items.*.item_name.required' => 'Item name is required for all items.',
            'line_items.*.item_name.max' => 'Item name must be at most 255 characters.',
            'line_items.*.description.max' => 'Description must be at most 255 characters.',
            'line_items.*.quantity.required' => 'Quantity is required for all items.',
            'line_items.*.quantity.min' => 'Quantity must be at least 1.',
            'line_items.*.quantity.integer' => 'Quantity must be a whole number.',
            'line_items.*.unit_price.required' => 'Unit price is required for all items.',
            'line_items.*.unit_price.min' => 'Unit price cannot be negative.',
            'line_items.*.unit_price.numeric' => 'Unit price must be a number.',
            'line_items.*.tax_rate.between' => 'Tax rate must be between 0 and 100.',
            'discount.min' => 'Discount cannot be negative.',
            'tax_percentage.required_if' => 'Tax percentage is required when tax is applicable.',
            'tax_percentage.in' => 'Tax percentage must be one of 0, 5, 12, 18, or 28.',
            'deposit_type.required_if' => 'Deposit type is required when deposit is required.',
            'deposit_type.in' => 'Deposit type must be percentage or fixed.',
            'deposit_amount.required_if' => 'Deposit amount is required when deposit is required.',
            'deposit_amount.min' => 'Deposit amount cannot be negative.',
            'approval_status.in' => 'Invalid approval status selected.',
            'reminders.*.follow_up_schedule.required_with' => 'Follow up schedule is required.',
            'reminders.*.reminder_type.required_with' => 'Reminder type is required.',
            'reminders.*.reminder_type.in' => 'Invalid reminder type selected.',
            'reminders.*.reminder_status.in' => 'Invalid reminder status selected.',
            'expires_at.after' => 'Expiry date must be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [
            'quote_number' => 'QT-' . now()->format('Ymd') . '-' . rand(1000, 9999),
            'status' => $this->status ?? 'draft',
            'equity_status' => $this->equity_status ?? 'not_applicable',
            'currency' => strtoupper($this->currency ?? 'USD'),
            'discount' => $this->discount ?? 0,
            'is_tax_applicable' => filter_var($this->is_tax_applicable ?? false, FILTER_VALIDATE_BOOLEAN),
            'deposit_required' => filter_var($this->deposit_required ?? false, FILTER_VALIDATE_BOOLEAN),
            'can_convert_to_job' => filter_var($this->can_convert_to_job ?? true, FILTER_VALIDATE_BOOLEAN),
            'approval_status' => $this->approval_status ?? 'pending',
        ];

        if (!$data['is_tax_applicable']) {
            $data['tax_percentage'] = 0;
        } else {
            $data['tax_percentage'] = (int) ($this->tax_percentage ?? 0);
        }

        if (!$this->has('client_id') && $this->has('customer_id')) {
            $data['client_id'] = $this->customer_id;
        }

        $this->merge($data);
    }
}
