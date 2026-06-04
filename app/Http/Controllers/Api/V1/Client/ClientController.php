<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Exceptions\CrossRoleEmailConflictException;
use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Api\V1\Clients\CreateClientRequest;
use App\Http\Requests\Api\V1\Clients\UpdateClientRequest;
use App\Http\Requests\Api\V1\Clients\GetClientsRequest;
use App\Http\Resources\Api\V1\Client\ClientCollection;
use App\Http\Resources\Api\V1\Client\ClientResource;
use App\Models\Customer;
use App\Services\Clients\ClientCreationService;
use App\Services\Clients\ClientQueryService;
use App\Services\Clients\ClientUpdateService;
use App\Services\Clients\ClientDeletionService;
use App\Services\Customer\CustomerAccountService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ClientController extends BaseController
{
    use ApiResponse;

    private ClientCreationService $clientCreationService;
    private ClientQueryService $clientQueryService;
    private ClientUpdateService $clientUpdateService;
    private ClientDeletionService $clientDeletionService;
    private CustomerAccountService $customerAccountService;

    public function __construct(
        ClientCreationService $clientCreationService,
        ClientQueryService $clientQueryService,
        ClientUpdateService $clientUpdateService,
        ClientDeletionService $clientDeletionService,
        CustomerAccountService $customerAccountService
    ) {
        $this->clientCreationService = $clientCreationService;
        $this->clientQueryService = $clientQueryService;
        $this->clientUpdateService = $clientUpdateService;
        $this->clientDeletionService = $clientDeletionService;
        $this->customerAccountService = $customerAccountService;
    }

    /**
     * Add a new client for a vendor
     */
    public function addClient(CreateClientRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $vendorId = $user->vendor_id;

            if (!$vendorId) {
                return $this->errorResponse(
                    'Authenticated user is not associated with a vendor.',
                    403
                );
            }

            $validatedData = $request->validated();

            // Explicitly set vendor_id from authenticated user
            $validatedData['vendor_id'] = $vendorId;

            $client = $this->clientCreationService->create($validatedData, auth()->id());

            $passwordSetup = [
                'email' => $client->email,
                'email_sent' => false,
                'expires_in_minutes' => 60,
                'mail_error' => null,
                'already_activated' => false,
            ];

            try {
                $customerName = trim((string) (
                    $client->contact_person_name
                    ?: $client->business_name
                    ?: ($client->first_name ?? '') . ' ' . ($client->last_name ?? '')
                ));

                $existingCustomer = Customer::where('email', $client->email)->first();

                if ($existingCustomer) {
                    if (empty($existingCustomer->password)) {
                        $setupResult = $this->customerAccountService->resendSetupLink($existingCustomer);
                        $passwordSetup['email_sent'] = (bool) $setupResult['email_sent'];
                        $passwordSetup['mail_error'] = $setupResult['mail_error'];
                        $passwordSetup['expires_in_minutes'] = $setupResult['expires_in_minutes'];
                    } else {
                        $passwordSetup['already_activated'] = true;
                    }
                } else {
                    $setupResult = $this->customerAccountService->createCustomer([
                        'name' => $customerName ?: 'Customer',
                        'email' => $client->email,
                        'phone' => $client->mobile_number,
                        'status' => $client->status === 'inactive' ? 'inactive' : 'active',
                    ]);

                    $passwordSetup['email_sent'] = (bool) $setupResult['email_sent'];
                    $passwordSetup['mail_error'] = $setupResult['mail_error'];
                    $passwordSetup['expires_in_minutes'] = $setupResult['expires_in_minutes'];
                }
            } catch (CrossRoleEmailConflictException $exception) {
                throw $exception;
            } catch (\Throwable $mailException) {
                $passwordSetup['mail_error'] = $mailException->getMessage();

                Log::error('Client created but customer setup mail provisioning failed.', [
                    'client_id' => $client->id,
                    'email' => $client->email,
                    'error' => $mailException->getMessage(),
                ]);
            }

            return $this->createdResponse(
                new ClientResource($client),
                $passwordSetup['already_activated']
                    ? 'Client added successfully. Customer account already active.'
                    : ($passwordSetup['email_sent']
                        ? 'Client added successfully. Set password link sent to customer email.'
                        : 'Client added successfully, but setup email could not be sent.'),
                [
                    'password_setup' => $passwordSetup,
                ]
            );
        } catch (CrossRoleEmailConflictException $e) {
            Log::warning('Cross-role email conflict while creating client-linked customer.', [
                'error' => $e->getMessage(),
                'existing_role' => $e->getExistingRole(),
                'vendor_id' => $vendorId ?? null,
            ]);

            return $this->errorResponse(
                $e->getMessage(),
                422,
                null,
                ['existing_role' => $e->getExistingRole()]
            );
        } catch (\Exception $e) {
            Log::error('Failed to add client', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendorId ?? null,
            ]);

            return $this->errorResponse(
                'Failed to add client. Please try again.',
                500
            );
        }
    }

    /**
     * Get all clients for a specific vendor with filtering and pagination
     */
    public function getVendorClients(GetClientsRequest $request): JsonResponse
    {
        try {
            // Get vendor_id from authenticated user
            $user = auth()->user();
            $vendorId = $user->vendor_id;

            if (!$vendorId) {
                return $this->errorResponse(
                    'Authenticated user is not associated with a vendor.',
                    403
                );
            }

            Log::info('=== GET VENDOR CLIENTS START ===', [
                'vendor_id' => $vendorId,
                'filters' => $request->all(),
                'user_id' => $user->id
            ]);

            $validated = $request->validated();
            $clients = $this->clientQueryService->getClients($vendorId, $validated, $validated['per_page'] ?? 15);

            $appliedFilters = $this->clientQueryService->getAppliedFilters($validated);

            return $this->successResponse(
                new ClientCollection($clients),
                'Clients retrieved successfully.',
                200,
                [
                    'vendor_id' => $vendorId,
                    'filters' => $appliedFilters,
                ]
            );
        } catch (\Exception $e) {
            Log::error('=== GET VENDOR CLIENTS END ===', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve clients. Please try again.',
                500
            );
        }
    }

    /**
     * Get a single client by ID for a specific vendor
     */
    public function getVendorClient(int $clientId): JsonResponse
    {
        try {
            // Get the authenticated user's vendor_id
            $user = auth()->user();
            $vendorId = $user->vendor_id;

            if (!$vendorId) {
                return $this->errorResponse(
                    'Authenticated user is not associated with a vendor.',
                    403
                );
            }

            Log::info('=== GET VENDOR CLIENT START ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
            ]);

            $client = $this->clientQueryService->getClient($vendorId, $clientId);

            if (!$client) {
                Log::warning('Client not found', [
                    'vendor_id' => $vendorId,
                    'client_id' => $clientId,
                ]);

                return $this->notFoundResponse('Client not found.');
            }

            Log::info('=== GET VENDOR CLIENT END ===', [
                'vendor_id' => $vendorId,
                'client_id' => $client->id,
                'status' => 'success'
            ]);

            return $this->successResponse(
                new ClientResource($client),
                'Client retrieved successfully.'
            );
        } catch (\Exception $e) {
            Log::error('=== GET VENDOR CLIENT END ===', [
                'client_id' => $clientId,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve client. Please try again.',
                500
            );
        }
    }

    /**
     * Update a client for a vendor
     */
    public function modifyClient(UpdateClientRequest $request, int $clientId): JsonResponse
    {
        
        try {
            // Get vendor_id from authenticated user
            $user = auth()->user();
            $vendorId = $user->vendor_id;

            if (!$vendorId) {
                return $this->errorResponse(
                    'Authenticated user is not associated with a vendor.',
                    403
                );
            }

            Log::info('=== MODIFY CLIENT START ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
                'user_id' => $user->id
            ]);

            // Check if client exists for this vendor
            $client = $this->clientQueryService->getClient($vendorId, $clientId);

            if (!$client) {
                return $this->notFoundResponse('Client not found.');
            }

            $validatedData = $request->validated();
            $client = $this->clientUpdateService->update($client, $validatedData, auth()->id());

            return $this->successResponse(
                new ClientResource($client),
                'Client updated successfully.'
            );
        } catch (\Exception $e) {
            Log::error('=== MODIFY CLIENT END ===', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to update client. Please try again.',
                500
            );
        }
    }

    /**
     * Delete/soft delete a client
     */
    public function removeClient(int $clientId): JsonResponse
    {
        try {
            // Get vendor_id from authenticated user
            $user = auth()->user();
            $vendorId = $user->vendor_id;

            if (!$vendorId) {
                return $this->errorResponse(
                    'Authenticated user is not associated with a vendor.',
                    403
                );
            }

            Log::info('=== REMOVE CLIENT START ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
                'deleted_by' => auth()->id(),
            ]);

            $client = $this->clientQueryService->getClient($vendorId, $clientId);

            if (!$client) {
                return $this->notFoundResponse('Client not found.');
            }

            // Check if client can be deleted
            $canDelete = $this->clientDeletionService->canDelete($client);

            if (!$canDelete['can_delete']) {
                return $this->errorResponse(
                    $canDelete['message'],
                    409
                );
            }

            $this->clientDeletionService->softDelete($client, auth()->id());

            return $this->successResponse(
                null,
                'Client deleted successfully.'
            );
        } catch (\Exception $e) {
            Log::error('=== REMOVE CLIENT END ===', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to delete client. Please try again.',
                500
            );
        }
    }
}
