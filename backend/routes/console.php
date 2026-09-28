<?php

declare(strict_types=1);

use App\Attributes\PerUserCommandReader;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Lorisleiva\Lody\Lody;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Every per-user command names its own cadence, so nothing here repeats it by hand.
Lody::classes(app_path('Actions'))
    ->each(function (string $class): void {
        if (! class_exists($class)) {
            return;
        }

        $perUserCommand = PerUserCommandReader::tryFor($class);

        if (! $perUserCommand instanceof App\Attributes\PerUserCommand) {
            return;
        }

        $perUserCommand->every->apply(Schedule::command($perUserCommand->name))->withoutOverlapping();
    });
