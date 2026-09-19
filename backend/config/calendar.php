<?php

declare(strict_types=1);

return [

    // Read-only, and nothing is connected by default: a calendar is the largest
    // privacy surface in the product and is opted into, never assumed.
    'driver' => env('CALENDAR_DRIVER', 'none'),

    'fixture_path' => storage_path('calendar-fixtures'),

    // How far ahead a sync looks. Preparation is a today concern, not a planner.
    'horizon_days' => (int) env('CALENDAR_HORIZON_DAYS', 2),

];
