<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Api\V1\Clients\CreateClientAvailabilityRequest;
use App\Http\Requests\Api\V1\Clients\UpdateClientAvailabilityRequest;
use App\Http\Resources\Api\V1\Client\ClientAvailabilityResource;
use App\Models\Client;
use App\Models\ClientAvailabilitySchedule;
use App\Services\Clients\ClientAvailabilityService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ClientAvailabilityController extends BaseController
{
    use ApiResponse;

    private ClientAvailabilityService $clientAvailabilityService;

    public function __construct(ClientAvailabilityService $clientAvailabilityService)
    {
        $this->clientAvailabilityService = $clientAvailabilityService;
    }

    /**
     * Get all availability schedules for a client
     */
    public function index(int $vendorId, int $clientId): JsonResponse
    {
        try {
            Log::info('=== GET CLIENT AVAILABILITY SCHEDULES START ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId
            ]);

            // Verify client belongs to vendor
            $client = $this->verifyClientBelongsToVendor($vendorId, $clientId);
            
            $schedules = $client->availabilitySchedules()
                ->orderBy('is_active', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('=== GET CLIENT AVAILABILITY SCHEDULES END ===', [
                'client_id' => $clientId,
                'schedules_count' => $schedules->count(),
                'status' => 'success'
            ]);

            return $this->successResponse(
                ClientAvailabilityResource::collection($schedules),
                'Client availability schedules retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('=== GET CLIENT AVAILABILITY SCHEDULES END ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve availability schedules. Please try again.',
                500
            );
        }
    }

    /**
     * Get active availability schedule for a client
     */
    public function getActive(int $vendorId, int $clientId): JsonResponse
    {
        try {
            Log::info('=== GET ACTIVE CLIENT AVAILABILITY START ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId
            ]);

            $client = $this->verifyClientBelongsToVendor($vendorId, $clientId);
            
            $activeSchedule = $client->activeAvailabilitySchedule;

            if (!$activeSchedule) {
                Log::info('No active availability schedule found', [
                    'client_id' => $clientId
                ]);
                
                return $this->successResponse(
                    null,
                    'No active availability schedule found'
                );
            }

            Log::info('=== GET ACTIVE CLIENT AVAILABILITY END ===', [
                'client_id' => $clientId,
                'schedule_id' => $activeSchedule->id,
                'status' => 'success'
            ]);

            return $this->successResponse(
                new ClientAvailabilityResource($activeSchedule),
                'Active availability schedule retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('=== GET ACTIVE CLIENT AVAILABILITY END ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve active availability schedule. Please try again.',
                500
            );
        }
    }

    /**
     * Create new availability schedule for client
     */
    public function store(CreateClientAvailabilityRequest $request, int $vendorId, int $clientId): JsonResponse
    {
        try {
            Log::info('=== CREATE CLIENT AVAILABILITY SCHEDULE START ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
                'data_keys' => array_keys($request->all()),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $client = $this->verifyClientBelongsToVendor($vendorId, $clientId);
            
            $validatedData = $request->validated();
            
            $schedule = $this->clientAvailabilityService->createSchedule(
                $client,
                $validatedData,
                auth()->id()
            );

            Log::info('=== CREATE CLIENT AVAILABILITY SCHEDULE END ===', [
                'client_id' => $clientId,
                'schedule_id' => $schedule->id,
                'status' => 'success'
            ]);

            return $this->createdResponse(
                new ClientAvailabilityResource($schedule),
                'Availability schedule created successfully.'
            );
        } catch (\Exception $e) {
            Log::error('=== CREATE CLIENT AVAILABILITY SCHEDULE END ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to create availability schedule. Please try again.',
                500
            );
        }
    }

    /**
     * Update availability schedule
     */
    public function update(UpdateClientAvailabilityRequest $request, int $vendorId, int $scheduleId): JsonResponse
    {
        try {
            Log::info('=== UPDATE CLIENT AVAILABILITY SCHEDULE START ===', [
                'vendor_id' => $vendorId,
                'schedule_id' => $scheduleId,
                'updates' => array_keys($request->all()),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $schedule = ClientAvailabilitySchedule::with('client')->findOrFail($scheduleId);
            
            // Verify schedule belongs to vendor's client
            if ($schedule->client->vendor_id != $vendorId) {
                return $this->notFoundResponse('Schedule not found for this vendor.');
            }

            $validatedData = $request->validated();
            
            $schedule = $this->clientAvailabilityService->updateSchedule(
                $schedule,
                $validatedData,
                auth()->id()
            );

            Log::info('=== UPDATE CLIENT AVAILABILITY SCHEDULE END ===', [
                'schedule_id' => $schedule->id,
                'status' => 'success'
            ]);

            return $this->successResponse(
                new ClientAvailabilityResource($schedule),
                'Availability schedule updated successfully.'
            );
        } catch (\Exception $e) {
            Log::error('=== UPDATE CLIENT AVAILABILITY SCHEDULE END ===', [
                'vendor_id' => $vendorId,
                'schedule_id' => $scheduleId,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to update availability schedule. Please try again.',
                500
            );
        }
    }

    /**
     * Deactivate schedule (soft delete)
     */
    public function destroy(int $vendorId, int $scheduleId): JsonResponse
    {
        try {
            Log::info('=== DELETE CLIENT AVAILABILITY SCHEDULE START ===', [
                'vendor_id' => $vendorId,
                'schedule_id' => $scheduleId,
                'deleted_by' => auth()->id(),
            ]);

            $schedule = ClientAvailabilitySchedule::with('client')->findOrFail($scheduleId);
            
            // Verify schedule belongs to vendor's client
            if ($schedule->client->vendor_id != $vendorId) {
                return $this->notFoundResponse('Schedule not found for this vendor.');
            }

            $this->clientAvailabilityService->deactivateSchedule($schedule, auth()->id());

            Log::info('=== DELETE CLIENT AVAILABILITY SCHEDULE END ===', [
                'schedule_id' => $scheduleId,
                'status' => 'success'
            ]);

            return $this->successResponse(
                null,
                'Availability schedule deactivated successfully.'
            );
        } catch (\Exception $e) {
            Log::error('=== DELETE CLIENT AVAILABILITY SCHEDULE END ===', [
                'vendor_id' => $vendorId,
                'schedule_id' => $scheduleId,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to deactivate availability schedule. Please try again.',
                500
            );
        }
    }

    /**
     * Check client availability for a specific date/time
     */
    public function checkAvailability(int $vendorId, int $clientId): JsonResponse
    {
        try {
            $request = request();
            
            Log::info('=== CHECK CLIENT AVAILABILITY START ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
                'date' => $request->get('date'),
                'start_time' => $request->get('start_time'),
                'end_time' => $request->get('end_time'),
            ]);

            $client = $this->verifyClientBelongsToVendor($vendorId, $clientId);
            
            $date = $request->get('date');
            $startTime = $request->get('start_time');
            $endTime = $request->get('end_time');

            // Validate required parameters
            if (!$date || !$startTime || !$endTime) {
                return $this->validationErrorResponse([
                    'date' => ['The date field is required.'],
                    'start_time' => ['The start time field is required.'],
                    'end_time' => ['The end time field is required.']
                ]);
            }

            $isAvailable = $this->clientAvailabilityService->checkAvailability(
                $client,
                $date,
                $startTime,
                $endTime
            );

            Log::info('=== CHECK CLIENT AVAILABILITY END ===', [
                'client_id' => $clientId,
                'is_available' => $isAvailable,
                'status' => 'success'
            ]);

            return $this->successResponse([
                'is_available' => $isAvailable,
                'date' => $date,
                'time_slot' => "{$startTime} - {$endTime}",
                'message' => $isAvailable ? 
                    'Client is available for this time slot' : 
                    'Client is not available for this time slot'
            ], 'Availability checked successfully');
        } catch (\Exception $e) {
            Log::error('=== CHECK CLIENT AVAILABILITY END ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to check availability. Please try again.',
                500
            );
        }
    }

    /**
     * Get available time slots for a client on specific date
     */
    public function getAvailableSlots(int $vendorId, int $clientId): JsonResponse
    {
        try {
            $request = request();
            
            Log::info('=== GET AVAILABLE SLOTS START ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
                'date' => $request->get('date'),
            ]);

            $client = $this->verifyClientBelongsToVendor($vendorId, $clientId);
            
            $date = $request->get('date');

            // Validate required parameter
            if (!$date) {
                return $this->validationErrorResponse([
                    'date' => ['The date field is required.']
                ]);
            }

            $slots = $this->clientAvailabilityService->getAvailableSlots($client, $date);

            Log::info('=== GET AVAILABLE SLOTS END ===', [
                'client_id' => $clientId,
                'slots_count' => count($slots),
                'status' => 'success'
            ]);

            return $this->successResponse([
                'date' => $date,
                'available_slots' => $slots,
                'total_slots' => count($slots)
            ], 'Available slots retrieved successfully');
        } catch (\Exception $e) {
            Log::error('=== GET AVAILABLE SLOTS END ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve available slots. Please try again.',
                500
            );
        }
    }

    /**
     * Helper to verify client belongs to vendor
     */
    private function verifyClientBelongsToVendor(int $vendorId, int $clientId): Client
    {
        $client = Client::where('vendor_id', $vendorId)
            ->where('id', $clientId)
            ->first();

        if (!$client) {
            throw new \Exception('Client not found for this vendor.');
        }

        return $client;
    }
}