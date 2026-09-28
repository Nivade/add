<?php

declare(strict_types=1);

use Nvade\Devtools\Rector\Preset;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;

return Preset::laravel(__DIR__)
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    // A byte change here changes AiRequest::cacheKey(), so every cached answer misses and the fixtures stop matching.
    ->withSkipPath(__DIR__.'/app/Support/Ai/Prompts.php')
    ->withSkip([
        // Getters on Candidate hide the model's shape from its own methods too.
        PrivatizeLocalGetterToPropertyRector::class,
    ]);
