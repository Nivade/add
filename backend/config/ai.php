<?php

declare(strict_types=1);

return [

    // canned answers every prompt deterministically and needs no credentials.
    'driver' => env('AI_DRIVER', 'canned'),

    'fixture_path' => storage_path('ai-fixtures'),

];
