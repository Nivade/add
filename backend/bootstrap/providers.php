<?php

use App\Providers\AiServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\CalendarServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\TypeScriptTransformerServiceProvider;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider;

return array_values(array_filter([
    AiServiceProvider::class,
    AppServiceProvider::class,
    CalendarServiceProvider::class,
    FortifyServiceProvider::class,
    // Extends a require-dev package; absent under `composer install --no-dev`.
    class_exists(TypeScriptTransformerApplicationServiceProvider::class)
        ? TypeScriptTransformerServiceProvider::class
        : null,
]));
