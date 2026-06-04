<?php

namespace App\Http\Resources\Api\V1\Client;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get formatted times (without seconds for display)
        $formattedStartTime = $this->formatTimeWithoutSeconds($this->preferred_start_time);
        $formattedEndTime = $this->formatTimeWithoutSeconds($this->preferred_end_time);
        $formattedLunchStart = $this->formatTimeWithoutSeconds($this->lunch_start);
        $formattedLunchEnd = $this->formatTimeWithoutSeconds($this->lunch_end);

        return [
            //'id' => $this->id,
            //'client_id' => $this->client_id,
            'available_days' => $this->available_days,
            //'available_days_display' => $this->getAvailableDaysListAttribute(),

            'preferred_start_time' => $formattedStartTime, // Display without seconds
            'preferred_end_time' => $formattedEndTime,     // Display without seconds
            'has_lunch_break' => (bool) $this->has_lunch_break,
            'lunch_start' => $formattedLunchStart,         // Display without seconds
            'lunch_end' => $formattedLunchEnd,             // Display without seconds
            'notes' => $this->notes,
            //'schedule_status' => $this->getScheduleStatusAttribute(),


            // Computed properties - Use full times for Carbon parsing
            //'time_slot_display' => $this->getTimeSlotDisplay($fullStartTime, $fullEndTime),
            //'lunch_break_display' => $this->getLunchBreakDisplay($fullLunchStart, $fullLunchEnd),

            // 'schedule_validity_display' => $this->schedule_start_date ?
            //     Carbon::parse($this->schedule_start_date)->format('M d, Y') .
            //     ($this->schedule_end_date ? ' to ' . Carbon::parse($this->schedule_end_date)->format('M d, Y') : ' (Ongoing)') : null,
        ];
    }

    /**
     * Format time without seconds for display
     */
    private function formatTimeWithoutSeconds(?string $time): ?string
    {
        if (!$time) return null;

        // Remove seconds if present
        if (strlen($time) > 5 && strpos($time, ':') === 2) {
            return substr($time, 0, 5); // Keep only HH:MM
        }

        return $time;
    }

    /**
     * Get formatted time slot display
     */
    private function getTimeSlotDisplay(?string $startTime, ?string $endTime): ?string
    {
        if (!$startTime || !$endTime) {
            return null;
        }

        try {
            // Try to parse with seconds first
            $start = Carbon::createFromFormat('H:i:s', $this->ensureTimeFormat($startTime));
            $end = Carbon::createFromFormat('H:i:s', $this->ensureTimeFormat($endTime));
            return $start->format('g:i A') . ' - ' . $end->format('g:i A');
        } catch (\Exception $e) {
            try {
                // Fallback: try without seconds
                $start = Carbon::createFromFormat('H:i', $this->ensureTimeFormat($startTime, false));
                $end = Carbon::createFromFormat('H:i', $this->ensureTimeFormat($endTime, false));
                return $start->format('g:i A') . ' - ' . $end->format('g:i A');
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    /**
     * Get formatted lunch break display
     */
    private function getLunchBreakDisplay(?string $lunchStart, ?string $lunchEnd): ?string
    {
        if (!$this->has_lunch_break || !$lunchStart || !$lunchEnd) {
            return null;
        }

        try {
            $start = Carbon::createFromFormat('H:i:s', $this->ensureTimeFormat($lunchStart));
            $end = Carbon::createFromFormat('H:i:s', $this->ensureTimeFormat($lunchEnd));
            return $start->format('g:i A') . ' - ' . $end->format('g:i A');
        } catch (\Exception $e) {
            try {
                $start = Carbon::createFromFormat('H:i', $this->ensureTimeFormat($lunchStart, false));
                $end = Carbon::createFromFormat('H:i', $this->ensureTimeFormat($lunchEnd, false));
                return $start->format('g:i A') . ' - ' . $end->format('g:i A');
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    /**
     * Ensure time has proper format (with or without seconds)
     */
    private function ensureTimeFormat(?string $time, bool $withSeconds = true): ?string
    {
        if (!$time) return null;

        // If time is already in HH:MM:SS format
        if (strlen($time) === 8 && substr_count($time, ':') === 2) {
            return $withSeconds ? $time : substr($time, 0, 5);
        }

        // If time is in HH:MM format
        if (strlen($time) === 5 && substr_count($time, ':') === 1) {
            return $withSeconds ? $time . ':00' : $time;
        }

        // If time is in H:MM format (single digit hour)
        if (strlen($time) === 4 && strpos($time, ':') === 1) {
            $time = '0' . $time; // Pad with leading zero
            return $withSeconds ? $time . ':00' : $time;
        }

        return $time;
    }
}
