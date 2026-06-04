<?php

namespace App\Services\Dispatch;

use App\Models\Schedule;
use Carbon\Carbon;

class ScheduleDispatchService
{
    public function hasCrewOverlap(int $vendorId, int $crewId, string $startDateTime, string $endDateTime, ?int $excludeScheduleId = null): bool
    {
        $query = Schedule::query()
            ->where('vendor_id', $vendorId)
            ->where('dispatch_crew_id', $crewId)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where(function ($inner) use ($startDateTime, $endDateTime) {
                $inner->where('start_datetime', '<', $endDateTime)
                    ->where('end_datetime', '>', $startDateTime);
            });

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return $query->exists();
    }

    public function mapDispatchStatus(?int $crewId, bool $isCrewBusy, ?string $requestedStatus = null): string
    {
        if (!$crewId) {
            return 'pending';
        }

        if ($isCrewBusy) {
            return 'pending';
        }

        if ($requestedStatus && in_array($requestedStatus, ['pending', 'assigned', 'in_progress', 'completed', 'cancelled'], true)) {
            return $requestedStatus;
        }

        return 'assigned';
    }

    public function combineDateAndTime(string $scheduleDate, string $time): string
    {
        return Carbon::parse($scheduleDate . ' ' . $time)->format('Y-m-d H:i:s');
    }

    public function eventColorForSchedule(Schedule $schedule): string
    {
        $title = strtolower((string) ($schedule->title ?? ''));

        if (str_contains($title, 'inspection')) {
            return '#d64545';
        }

        if (str_contains($title, 'repair')) {
            return '#e48a2a';
        }

        if (str_contains($title, 'service')) {
            return '#1f77d0';
        }

        return '#49a942';
    }
}
