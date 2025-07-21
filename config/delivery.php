<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Driver Assignment Limits
    |--------------------------------------------------------------------------
    |
    | Maximum number of simultaneous assignments a driver can handle
    |
    */
    'max_assignments_per_driver' => env('MAX_ASSIGNMENTS_PER_DRIVER', 3),

    /*
    |--------------------------------------------------------------------------
    | Default Travel Duration
    |--------------------------------------------------------------------------
    |
    | Default travel time in hours when no specific region duration is set
    |
    */
    'default_travel_duration_hours' => env('DEFAULT_TRAVEL_DURATION_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Truck Capacity Thresholds
    |--------------------------------------------------------------------------
    |
    | Percentage thresholds for truck capacity warnings
    |
    */
    'capacity_thresholds' => [
        'warning' => 0.7, // 70% capacity
        'critical' => 0.9 // 90% capacity
    ],
];