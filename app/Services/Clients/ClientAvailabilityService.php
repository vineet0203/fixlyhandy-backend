<?php

namespace App\Services\Clients;

use App\Models\Client;
use App\Models\ClientAvailabilitySchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientAvailabilityService
{
    /**
     * Create a new availability schedule for client
     */
    public function createSchedule(Client $client, array $data, int $createdBy): ClientAvailabilitySchedule
    {
        DB::beginTransaction();

        try {
            Log::info('=== CREATE AVAILABILITY SCHEDULE START ===', [
                'client_id' => $client->id,
                'vendor_id' => $client->vendor_id,
                'data_keys' => array_keys($data),
                'created_by' => $createdBy
            ]);

            // If activating this schedule, deactivate others
            if (($data['is_active'] ?? true) === true) {
                $this->deactivateOtherSchedules($client);
            }

            $schedule = ClientAvailabilitySchedule::create(array_merge(
                $data,
                [
                    'client_id' => $client->id,
                    'created_by' => $createdBy
                ]
            ));

            DB::commit();

            Log::info('=== CREATE AVAILABILITY SCHEDULE END ===', [
                'schedule_id' => $schedule->id,
                'client_id' => $client->id,
                'is_active' => $schedule->is_active,
                'created_by' => $createdBy
            ]);

            return $schedule;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create availability schedule', [
                'error' => $e->getMessage(),
                'client_id' => $client->id,
                'created_by' => $createdBy,
            ]);
            throw $e;
        }
    }

    /**
     * Update existing schedule
     */
    public function updateSchedule(ClientAvailabilitySchedule $schedule, array $data, int $updatedBy): ClientAvailabilitySchedule
    {
        DB::beginTransaction();

        try {
            Log::info('=== UPDATE AVAILABILITY SCHEDULE START ===', [
                'schedule_id' => $schedule->id,
                'client_id' => $schedule->client_id,
                'updates' => array_keys($data),
                'updated_by' => $updatedBy
            ]);

            // If activating this schedule, deactivate others
            if (isset($data['is_active']) && $data['is_active'] === true) {
                $this->deactivateOtherSchedules($schedule->client, $schedule->id);
            }

            $schedule->update(array_merge(
                $data,
                ['updated_by' => $updatedBy]
            ));

            DB::commit();

            Log::info('=== UPDATE AVAILABILITY SCHEDULE END ===', [
                'schedule_id' => $schedule->id,
                'is_active' => $schedule->is_active,
                'updated_by' => $updatedBy
            ]);

            return $schedule->fresh();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update availability schedule', [
                'error' => $e->getMessage(),
                'schedule_id' => $schedule->id,
                'updated_by' => $updatedBy,
            ]);
            throw $e;
        }
    }

    /**
     * Deactivate schedule
     */
    public function deactivateSchedule(ClientAvailabilitySchedule $schedule, int $deletedBy): void
    {
        DB::beginTransaction();

        try {
            Log::info('=== DEACTIVATE AVAILABILITY SCHEDULE START ===', [
                'schedule_id' => $schedule->id,
                'client_id' => $schedule->client_id,
                'deleted_by' => $deletedBy
            ]);

            $schedule->update(['is_active' => false, 'updated_by' => $deletedBy]);
            $schedule->delete(); // soft delete

            DB::commit();

            Log::info('=== DEACTIVATE AVAILABILITY SCHEDULE END ===', [
                'schedule_id' => $schedule->id,
                'deleted_by' => $deletedBy
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to deactivate availability schedule', [
                'error' => $e->getMessage(),
                'schedule_id' => $schedule->id,
                'deleted_by' => $deletedBy,
            ]);
            throw $e;
        }
    }

    /**
     * Check if client is available at given date/time
     */
    public function checkAvailability(
        Client $client,
        string $date,
        string $startTime,
        string $endTime
    ): bool {
        Log::debug('Checking client availability', [
            'client_id' => $client->id,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);

        $dateObj = Carbon::parse($date);
        $dayOfWeek = strtolower($dateObj->englishDayOfWeek);

        // Get active schedule for client
        $schedule = $client->activeAvailabilitySchedule;

        if (!$schedule) {
            Log::debug('No active schedule found for client', ['client_id' => $client->id]);
            return false;
        }

        // Check if date is within schedule validity
        if ($dateObj->lt(Carbon::parse($schedule->schedule_start_date))) {
            Log::debug('Date is before schedule start date', [
                'date' => $date,
                'schedule_start_date' => $schedule->schedule_start_date
            ]);
            return false;
        }

        if ($schedule->schedule_end_date && $dateObj->gt(Carbon::parse($schedule->schedule_end_date))) {
            Log::debug('Date is after schedule end date', [
                'date' => $date,
                'schedule_end_date' => $schedule->schedule_end_date
            ]);
            return false;
        }

        // Check if day is available
        if (!in_array($dayOfWeek, $schedule->available_days ?? [])) {
            Log::debug('Day not in available days', [
                'day_of_week' => $dayOfWeek,
                'available_days' => $schedule->available_days
            ]);
            return false;
        }

        // Check if time is within preferred hours
        $startCarbon = Carbon::parse($startTime);
        $endCarbon = Carbon::parse($endTime);
        $preferredStart = Carbon::parse($schedule->preferred_start_time);
        $preferredEnd = Carbon::parse($schedule->preferred_end_time);

        if ($startCarbon->lt($preferredStart) || $endCarbon->gt($preferredEnd)) {
            Log::debug('Time slot outside preferred hours', [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'preferred_start' => $schedule->preferred_start_time,
                'preferred_end' => $schedule->preferred_end_time
            ]);
            return false;
        }

        // Check lunch break
        if ($schedule->has_lunch_break && $schedule->lunch_start && $schedule->lunch_end) {
            $lunchStart = Carbon::parse($schedule->lunch_start);
            $lunchEnd = Carbon::parse($schedule->lunch_end);

            // Check if requested time overlaps with lunch break
            if ($startCarbon->lt($lunchEnd) && $endCarbon->gt($lunchStart)) {
                Log::debug('Time slot overlaps with lunch break', [
                    'lunch_start' => $schedule->lunch_start,
                    'lunch_end' => $schedule->lunch_end
                ]);
                return false;
            }
        }

        Log::debug('Client is available for time slot', [
            'client_id' => $client->id,
            'date' => $date,
            'time_slot' => "$startTime - $endTime"
        ]);

        return true;
    }

    /**
     * Get available time slots for a client on specific date
     */
    public function getAvailableSlots(Client $client, string $date): array
    {
        $schedule = $client->activeAvailabilitySchedule;

        if (!$schedule) {
            Log::debug('No active schedule found for client', ['client_id' => $client->id]);
            return [];
        }

        $dateObj = Carbon::parse($date);
        $dayOfWeek = strtolower($dateObj->englishDayOfWeek);

        // Check if day is in available days
        if (!in_array($dayOfWeek, $schedule->available_days ?? [])) {
            Log::debug('Day not in available days', [
                'day_of_week' => $dayOfWeek,
                'available_days' => $schedule->available_days
            ]);
            return [];
        }

        // Generate 1-hour slots based on schedule
        $slots = [];
        $start = Carbon::parse($schedule->preferred_start_time);
        $end = Carbon::parse($schedule->preferred_end_time);

        if ($schedule->has_lunch_break && $schedule->lunch_start && $schedule->lunch_end) {
            $lunchStart = Carbon::parse($schedule->lunch_start);
            $lunchEnd = Carbon::parse($schedule->lunch_end);
        }

        while ($start < $end) {
            $slotEnd = $start->copy()->addHour();

            // Skip lunch break
            if (isset($lunchStart) && isset($lunchEnd)) {
                if ($start->lt($lunchEnd) && $slotEnd->gt($lunchStart)) {
                    $start = $lunchEnd->copy();
                    continue;
                }
            }

            if ($slotEnd <= $end) {
                $slots[] = [
                    'start_time' => $start->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'display' => $start->format('g:i A') . ' - ' . $slotEnd->format('g:i A')
                ];
            }

            $start->addHour();
        }

        Log::debug('Generated available slots', [
            'client_id' => $client->id,
            'date' => $date,
            'total_slots' => count($slots)
        ]);

        return $slots;
    }

    /**
     * Deactivate all other active schedules for client
     */
    private function deactivateOtherSchedules(Client $client, ?int $excludeScheduleId = null): void
    {
        $query = ClientAvailabilitySchedule::where('client_id', $client->id)
            ->where('is_active', true);

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        $query->update(['is_active' => false]);

        Log::debug('Deactivated other schedules', [
            'client_id' => $client->id,
            'excluded_schedule_id' => $excludeScheduleId
        ]);
    }

    /**
     * Get client's next available date
     */
    public function getNextAvailableDate(Client $client, ?string $fromDate = null): ?string
    {
        $schedule = $client->activeAvailabilitySchedule;

        if (!$schedule) {
            return null;
        }

        $fromDate = $fromDate ? Carbon::parse($fromDate) : now();

        // Find next available day
        for ($i = 0; $i < 30; $i++) { // Check next 30 days
            $checkDate = $fromDate->copy()->addDays($i);
            $dayOfWeek = strtolower($checkDate->englishDayOfWeek);

            if (in_array($dayOfWeek, $schedule->available_days ?? [])) {
                // Check if date is within schedule validity
                if ($checkDate->gte(Carbon::parse($schedule->schedule_start_date))) {
                    if (!$schedule->schedule_end_date || $checkDate->lte(Carbon::parse($schedule->schedule_end_date))) {
                        return $checkDate->toDateString();
                    }
                }
            }
        }

        return null;
    }
}
