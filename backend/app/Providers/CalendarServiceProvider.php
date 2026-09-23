<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\CalendarSource;
use App\Support\Calendar\Sources\FakeCalendarSource;
use App\Support\Calendar\Sources\FixtureCalendarSource;
use App\Support\Calendar\Sources\IcsCalendarSource;
use App\Support\Calendar\Sources\NullCalendarSource;
use Illuminate\Support\ServiceProvider;

final class CalendarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton so a test can push events into the fake and the action reads the same one.
        $this->app->singleton(CalendarSource::class, fn (): CalendarSource => match (config('calendar.driver')) {
            IcsCalendarSource::NAME => new IcsCalendarSource,
            'fixture' => new FixtureCalendarSource,
            'fake' => new FakeCalendarSource,
            default => new NullCalendarSource,
        });
    }
}
