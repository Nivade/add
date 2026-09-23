<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /** Kept out of database/migrations so the test suite never pays for tables only the debug container reads. */
    public function register(): void
    {
        $this->loadMigrationsFrom(database_path('telescope-migrations'));

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(fn (IncomingEntry $entry): bool => $isLocal
            || $entry->isReportableException()
            || $entry->isFailedRequest()
            || $entry->isFailedJob()
            || $entry->isScheduledTask()
            || $entry->hasMonitoredTag());
    }

    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /** Telescope::auth already opens this locally, and the debug container is the only place it registers. */
    protected function gate(): void
    {
        Gate::define('viewTelescope', fn (): false => false);
    }
}
