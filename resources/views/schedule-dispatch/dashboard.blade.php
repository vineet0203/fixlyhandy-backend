<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Schedule & Dispatch</title>
    @vite(['resources/css/app.css', 'resources/css/schedule-dispatch.css', 'resources/js/app.js', 'resources/js/schedule-dispatch.js'])
</head>
<body>
    <div class="schedule-dashboard" id="schedule-dashboard" data-api-base="/api" data-initial-view="timeGridWeek">
        <div class="schedule-layout">
            <aside class="schedule-left-panel">
                @include('schedule-dispatch.partials.schedule-filters')
                @include('schedule-dispatch.partials.crew-members', ['crews' => $crews])
            </aside>

            <main class="schedule-main-panel">
                @include('schedule-dispatch.partials.schedule-header')
                @include('schedule-dispatch.partials.schedule-calendar')
                @include('schedule-dispatch.partials.route-optimization')
            </main>

            <aside class="schedule-right-panel">
                @include('schedule-dispatch.partials.upcoming-jobs')
            </aside>
        </div>
    </div>
</body>
</html>
