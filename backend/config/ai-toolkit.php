<?php

declare(strict_types=1);

// Only openai is read: drivers and fixtures are chosen in config/ai.php. Env names are this app's own.
return [

    'openai' => [
        'model' => env('AI_MODEL', 'gpt-5.6-luna'),
        'reasoning_effort' => env('AI_REASONING_EFFORT', 'low'),
        'timeout' => (int) env('AI_TIMEOUT', 30),
        'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 900),
        'rate_limit' => [
            'max_attempts' => (int) env('AI_RATE_LIMIT_ATTEMPTS', 30),
            'decay_seconds' => (int) env('AI_RATE_LIMIT_DECAY', 60),
        ],
    ],

];
