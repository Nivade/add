<?php

declare(strict_types=1);

use App\Providers\AiServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\CalendarServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\IngestionServiceProvider;
use App\Providers\TelescopeServiceProvider;
use App\Providers\TypeScriptTransformerServiceProvider;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider;

return array_values(array_filter([
    AiServiceProvider::class,
    AppServiceProvider::class,
    CalendarServiceProvider::class,
    FortifyServiceProvider::class,
    IngestionServiceProvider::class,
    // Extends a require-dev package; absent under `composer install --no-dev`.
    class_exists(TypeScriptTransformerApplicationServiceProvider::class)
        ? TypeScriptTransformerServiceProvider::class
        : null,
    // Same, plus the env gate: registering this provider is what loads Telescope's migrations.
    class_exists(TelescopeApplicationServiceProvider::class) && filter_var(env('TELESCOPE_ENABLED', false), FILTER_VALIDATE_BOOL)
        ? TelescopeServiceProvider::class
        : null,
]));
