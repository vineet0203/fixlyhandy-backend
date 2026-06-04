<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerInvoiceController extends BaseController
{
    public function index(): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();
        $this->syncCustomerInvoices($customer);

        $invoices = Invoice::query()
            ->with(['items', 'employee'])
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->paginate(15);

        // Transform collection to include calculated totals
        $transformedInvoices = $invoices->getCollection()->map(function ($invoice) {
            $totalAmount = $invoice->items->sum('final_amount');
            $vatTotal = $invoice->items->sum(function ($item) {
                return round(((float) $item->amount * (float) $item->vat) / 100, 2);
            });
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'bill_date' => $invoice->bill_date,
                'payment_deadline' => $invoice->payment_deadline,
                'status' => $invoice->status,
                'customer_status' => $invoice->customer_status,
                'reject_reason' => $invoice->reject_reason,
                'totals' => [
                    'total_amount' => $totalAmount,
                    'weekly_amount' => $invoice->items->sum('amount'),
                    'mileage' => $invoice->items->sum('mileage'),
                    'other_expense' => $invoice->items->sum('other_expense'),
                    'vat' => $vatTotal,
                ],
                'items' => $invoice->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'job_name' => $item->job_name,
                        'mileage' => $item->mileage,
                        'other_expense' => $item->other_expense,
                        'amount' => $item->amount,
                        'vat' => $item->vat,
                        'final_amount' => $item->final_amount,
                    ];
                }),
                'employee' => $invoice->employee ? [
                    'id' => $invoice->employee->id,
                    'name' => $invoice->employee->name,
                ] : null,
                'billing_address' => $invoice->billing_address,
                'note' => $invoice->note,
                'terms_conditions' => $invoice->terms_conditions,
            ];
        });

        $invoices->setCollection($transformedInvoices);

        return $this->successResponse(
            $invoices,
            'Customer invoices retrieved successfully.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();
        $this->syncCustomerInvoices($customer);

        $invoice = Invoice::query()
            ->with(['items', 'employee'])
            ->where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$invoice) {
            return $this->notFoundResponse('Invoice not found.');
        }

        $totalAmount = $invoice->items->sum('final_amount');
        $vatTotal = $invoice->items->sum(function ($item) {
            return round(((float) $item->amount * (float) $item->vat) / 100, 2);
        });
        
        $data = [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'bill_date' => $invoice->bill_date,
            'delivery_date' => $invoice->delivery_date,
            'payment_deadline' => $invoice->payment_deadline,
            'status' => $invoice->status,
            'customer_status' => $invoice->customer_status,
            'reject_reason' => $invoice->reject_reason,
            'totals' => [
                'total_amount' => $totalAmount,
                'weekly_amount' => $invoice->items->sum('amount'),
                'mileage' => $invoice->items->sum('mileage'),
                'other_expense' => $invoice->items->sum('other_expense'),
                'vat' => $vatTotal,
            ],
            'items' => $invoice->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'job_name' => $item->job_name,
                    'mileage' => $item->mileage,
                    'other_expense' => $item->other_expense,
                    'amount' => $item->amount,
                    'vat' => $item->vat,
                    'final_amount' => $item->final_amount,
                ];
            }),
            'employee' => $invoice->employee ? [
                'id' => $invoice->employee->id,
                'name' => $invoice->employee->name,
            ] : null,
            'billing_address' => $invoice->billing_address,
            'note' => $invoice->note,
            'terms_conditions' => $invoice->terms_conditions,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Customer invoice retrieved successfully.',
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
            'code' => 200,
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:accepted,rejected',
            'reject_reason' => 'required_if:action,rejected|nullable|string',
        ]);

        $customer = $this->getAuthenticatedCustomer();
        $this->syncCustomerInvoices($customer);

        $invoice = Invoice::query()
            ->where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$invoice) {
            return $this->notFoundResponse('Invoice not found.');
        }

        $action = $request->input('action');
        $invoice->customer_status = $action;
        
        if ($action === 'rejected') {
            $invoice->reject_reason = $request->input('reject_reason');
        } else {
            $invoice->reject_reason = null;
        }

        $invoice->save();

        return response()->json([
            'success' => true,
            'message' => 'Invoice status updated successfully.',
            'data' => [
                'id' => $invoice->id,
                'customer_status' => $invoice->customer_status,
                'reject_reason' => $invoice->reject_reason,
            ],
            'timestamp' => now()->toIso8601String(),
            'code' => 200,
        ]);
    }

    private function getAuthenticatedCustomer(): Customer
    {
        $customerData = request()->attributes->get('customer');
        return Customer::findOrFail((int) $customerData['id']);
    }

    private function syncCustomerInvoices(Customer $customer): void
    {
        // Assuming client_email is the field used to associate, similar to quotes
        // Or if there's no client_email directly on invoice, we might have to use client->email
        // I will use whereHas('client', function($q) use($customer) { $q->where('email', $customer->email); })
        // Let's implement this robustly.
        Invoice::query()
            ->whereNull('customer_id')
            ->whereHas('client', function ($query) use ($customer) {
                $query->where('email', $customer->email);
            })
            ->update(['customer_id' => $customer->id]);
    }
}
