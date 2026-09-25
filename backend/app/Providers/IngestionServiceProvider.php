<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\IngestionSource;
use App\Support\Ingestion\ManualIngestionSource;
use Illuminate\Support\ServiceProvider;

final class IngestionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The only adapter this phase builds; a config-driven match arrives with the next one.
        $this->app->bind(IngestionSource::class, ManualIngestionSource::class);
    }
}
