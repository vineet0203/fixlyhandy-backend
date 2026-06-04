<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Api\V1\Customers\CustomerQuoteApprovalRequest;
use App\Http\Requests\Api\V1\Customers\CustomerQuoteDecisionRequest;
use App\Http\Resources\Api\V1\Quote\QuoteCollection;
use App\Http\Resources\Api\V1\Quote\QuoteResource;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\CustomerNotification;
use Illuminate\Http\JsonResponse;

class CustomerQuoteController extends BaseController
{
    public function index(): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();
        $this->syncCustomerQuotes($customer);

        $quotes = Quote::query()
            ->with(['items'])
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->paginate(15);

        return $this->successResponse(
            new QuoteCollection($quotes),
            'Customer quotes retrieved successfully.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();
        $this->syncCustomerQuotes($customer);

        $quote = Quote::query()
            ->with(['items', 'reminders'])
            ->where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$quote) {
            return $this->notFoundResponse('Quote not found.');
        }

        $resource = new QuoteResource($quote);

        return response()->json([
            'success' => true,
            'message' => 'Customer quote retrieved successfully.',
            'data' => $resource->toArray(request()),
            'timestamp' => now()->toIso8601String(),
            'code' => 200,
        ]);
    }

    public function updateApproval(int $id, CustomerQuoteApprovalRequest $request): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();
        $this->syncCustomerQuotes($customer);

        $quote = $this->findCustomerQuote($id, $customer);
        if (!$quote) {
            return $this->notFoundResponse('Quote not found.');
        }

        $action = strtolower($request->validated()['action']);
        $quote->approval_status = $action === 'accepted' ? 'accepted' : 'rejected';
        $quote->status = $action === 'accepted' ? 'approved' : 'rejected';
        $quote->approval_date = now();
        $quote->approval_action_date = now();
        if (!empty($request->validated()['customer_signature'])) {
            $quote->customer_signature = $request->validated()['customer_signature'];
        }
        $quote->save();

        return response()->json([
            'success' => true,
            'message' => 'Client approval updated successfully.',
            'data' => [
                'id' => $quote->id,
                'approval_status' => $quote->approval_status,
                'customer_signature' => $quote->customer_signature,
            ],
            'timestamp' => now()->toIso8601String(),
            'code' => 200,
        ]);
    }

    public function decide(int $id, CustomerQuoteDecisionRequest $request): JsonResponse
    {
        return $this->handleDecision($id, $request->validated());
    }

    public function submit(int $id, CustomerQuoteDecisionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['action'] = 'submit';
        return $this->handleDecision($id, $validated);
    }

    private function handleDecision(int $id, array $validated): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();
        $this->syncCustomerQuotes($customer);
        $quote = $this->findCustomerQuote($id, $customer);

        if (!$quote) {
            return $this->notFoundResponse('Quote not found.');
        }

        $action = $validated['action'] ?? null;
        if (!$action) {
            return $this->validationErrorResponse(['action' => ['The action field is required.']]);
        }

        if ($action === 'approve' || $action === 'submit') {
            $quote->approval_status = 'accepted';
            $quote->approval_action_date = now();
            $quote->approval_date = now();
        }

        if ($action === 'reject') {
            $quote->approval_status = 'rejected';
            $quote->approval_action_date = now();
            $quote->approval_date = now();
        }

        $quote->save();

        if ($quote->job_id) {
            \App\Models\Job::where('id', $quote->job_id)
                ->whereNull('customer_id')
                ->update(['customer_id' => $customer->id]);
        }

        return $this->successResponse(
            new QuoteResource($quote->fresh(['items'])),
            $action === 'reject' ? 'Quote rejected successfully.' : 'Quote updated successfully.'
        );
    }

    private function getAuthenticatedCustomer(): Customer
    {
        $customerData = request()->attributes->get('customer');
        return Customer::findOrFail((int) $customerData['id']);
    }

    private function findCustomerQuote(int $quoteId, Customer $customer): ?Quote
    {
        return Quote::query()
            ->where('id', $quoteId)
            ->where('customer_id', $customer->id)
            ->first();
    }

    private function syncCustomerQuotes(Customer $customer): void
    {
        Quote::query()
            ->whereNull('customer_id')
            ->where('client_email', $customer->email)
            ->update(['customer_id' => $customer->id]);
    }
}
