<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Exceptions\CrossRoleEmailConflictException;
use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Api\V1\Customers\CreateCustomerRequest;
use App\Http\Requests\Api\V1\Customers\ResendCustomerSetupLinkRequest;
use App\Models\Customer;
use App\Services\Customer\CustomerAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomerController extends BaseController
{
    public function __construct(private CustomerAccountService $customerAccountService) {}

    public function store(CreateCustomerRequest $request): JsonResponse
    {
        try {
            $result = $this->customerAccountService->createCustomer($request->validated());
            $customer = $result['customer'];

            if (!$result['email_sent']) {
                Log::warning('Customer created but setup email failed to send.', [
                    'customer_id' => $customer->id,
                    'email' => $customer->email,
                    'mail_error' => $result['mail_error'],
                ]);
            }

            return $this->createdResponse([
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'role' => $customer->role,
                'status' => $customer->status,
                'password_setup' => [
                    'email_sent' => $result['email_sent'],
                    'expires_in_minutes' => $result['expires_in_minutes'],
                    'mail_error' => $result['mail_error'],
                ],
            ], $result['email_sent']
                ? 'Customer created successfully. Set password link sent to email.'
                : 'Customer created successfully, but setup email could not be sent.');
        } catch (CrossRoleEmailConflictException $exception) {
            Log::warning('Cross-role email conflict while creating customer.', [
                'error' => $exception->getMessage(),
                'existing_role' => $exception->getExistingRole(),
            ]);

            return $this->errorResponse(
                $exception->getMessage(),
                422,
                null,
                ['existing_role' => $exception->getExistingRole()]
            );
        } catch (\Throwable $exception) {
            Log::error('Failed to create customer.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->errorResponse('Failed to create customer. Please try again.', 500);
        }
    }

    public function resendSetupLink(ResendCustomerSetupLinkRequest $request): JsonResponse
    {
        try {
            $customer = Customer::find($request->validated('customer_id'));

            if (!$customer) {
                return $this->notFoundResponse('Customer not found.');
            }

            $result = $this->customerAccountService->resendSetupLink($customer);

            if (!$result['email_sent']) {
                Log::warning('Customer setup link resend failed.', [
                    'customer_id' => $customer->id,
                    'email' => $customer->email,
                    'mail_error' => $result['mail_error'],
                ]);
            }

            return $this->successResponse([
                'id' => $customer->id,
                'email' => $customer->email,
                'password_setup' => [
                    'email_sent' => $result['email_sent'],
                    'expires_in_minutes' => $result['expires_in_minutes'],
                    'mail_error' => $result['mail_error'],
                ],
            ], $result['email_sent']
                ? 'Customer setup password link sent successfully.'
                : 'Customer setup link could not be sent.');
        } catch (HttpException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        } catch (\Throwable $exception) {
            Log::error('Failed to resend customer setup link.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->errorResponse('Failed to resend setup link. Please try again.', 500);
        }
    }
}