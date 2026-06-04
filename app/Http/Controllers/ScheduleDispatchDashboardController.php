<?php

namespace App\Http\Controllers;

use App\Models\Crew;
use Illuminate\View\View;

class ScheduleDispatchDashboardController extends Controller
{
    public function __invoke(): View
    {
        $crews = Crew::query()
            ->with(['members.employee'])
            ->orderBy('name')
            ->get();

        return view('schedule-dispatch.dashboard', [
            'crews' => $crews,
        ]);
    }
}
