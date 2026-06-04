<?php

namespace App\Http\Requests\Api\V1\Quotes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $quoteId = $this->route('quote');

        return [
            // Section 1: Quote Details
            'title' => 'sometimes|string|max:255',
            'quote_due_date' => 'sometimes|nullable|date',
            'client_id' => 'sometimes|exists:clients,id',
            'customer_id' => 'sometimes|exists:clients,id',
            'client_name' => 'sometimes|string|max:191',
            'client_email' => 'sometimes|email|max:191',
            'status' => 'sometimes|in:draft,sent,pending,accepted,rejected,expired',
            'equity_status' => 'sometimes|in:pending,approved,rejected,not_applicable',
            'currency' => 'sometimes|string|size:3|in:USD,EUR,GBP,JPY,CAD,AUD',

            // Section 2: Line Items
            'line_items' => 'sometimes|array',
            'line_items.*.id' => 'nullable|exists:quote_items,id',
            'line_items.*.item_name' => 'required_with:line_items|string|max:255',
            'line_items.*.description' => 'nullable|string|max:255',
            'line_items.*.quantity' => 'required_with:line_items|integer|min:1',
            'line_items.*.unit_price' => 'required_with:line_items|numeric|min:0',
            'line_items.*.tax_rate' => 'nullable|numeric|between:0,100',
            'line_items.*.package_id' => 'nullable|exists:packages,id',
            'line_items.*._delete' => 'sometimes|boolean',

            // Section 3: Pricing Summary
            'discount' => 'nullable|numeric|min:0',
            'is_tax_applicable' => 'sometimes|boolean',
            'tax_percentage' => 'required_if:is_tax_applicable,true|integer|between:0,100',
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
            'reminders.*.id' => 'nullable|sometimes',
            'reminders.*.follow_up_schedule' => 'required_with:reminders|date',
            'reminders.*.reminder_type' => 'required_with:reminders|in:email,sms,notification',
            'reminders.*.reminder_status' => 'sometimes|in:scheduled,sent,cancelled',
            'reminders.*._delete' => 'sometimes|boolean',

            // Section 6: Conversion to Job
            'can_convert_to_job' => 'sometimes|boolean',
            'job_id' => 'nullable|exists:jobs,id',

            // Meta
            'notes' => 'nullable|string',
            'expires_at' => 'nullable|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'Title must be at most 255 characters.',
            'client_id.exists' => 'Selected client does not exist.',
            'client_email.email' => 'Please enter a valid email address.',
            'status.in' => 'Invalid quote status selected.',
            'equity_status.in' => 'Invalid equity status selected.',
            'currency.size' => 'Currency must be 3 characters.',
            'currency.in' => 'Invalid currency selected.',
            'line_items.*.item_name.required_with' => 'Item name is required for all items.',
            'line_items.*.item_name.max' => 'Item name must be at most 255 characters.',
            'line_items.*.description.max' => 'Description must be at most 255 characters.',
            'line_items.*.quantity.required_with' => 'Quantity is required for all items.',
            'line_items.*.quantity.min' => 'Quantity must be at least 1.',
            'line_items.*.quantity.integer' => 'Quantity must be a whole number.',
            'line_items.*.unit_price.required_with' => 'Unit price is required for all items.',
            'line_items.*.unit_price.min' => 'Unit price cannot be negative.',
            'line_items.*.unit_price.numeric' => 'Unit price must be a number.',
            'line_items.*.tax_rate.between' => 'Tax rate must be between 0 and 100.',
            'discount.min' => 'Discount cannot be negative.',
            'tax_percentage.required_if' => 'Tax percentage is required when tax is applicable.',
            'tax_percentage.between' => 'Tax percentage must be between 0 and 100.',
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
            'discount' => $this->discount ?? 0,
            'is_tax_applicable' => !is_null($this->is_tax_applicable) ? filter_var($this->is_tax_applicable, FILTER_VALIDATE_BOOLEAN) : null,
            'deposit_required' => !is_null($this->deposit_required) ? filter_var($this->deposit_required, FILTER_VALIDATE_BOOLEAN) : null,
            'can_convert_to_job' => !is_null($this->can_convert_to_job) ? filter_var($this->can_convert_to_job, FILTER_VALIDATE_BOOLEAN) : null,
        ];

        if (!is_null($data['is_tax_applicable'])) {
            $data['tax_percentage'] = $data['is_tax_applicable']
                ? (int) ($this->tax_percentage ?? 0)
                : 0;
        }

        // Ensure currency is uppercase
        if ($this->has('currency')) {
            $data['currency'] = strtoupper($this->currency);
        }

        // Map line_items to items if present
        if ($this->has('line_items')) {
            $data['items'] = $this->line_items;
        }

        if (!$this->has('client_id') && $this->has('customer_id')) {
            $data['client_id'] = $this->customer_id;
        }

        $this->merge($data);
    }
}