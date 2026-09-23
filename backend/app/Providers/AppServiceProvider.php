<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Appointment;
use App\Enums\AppointmentKind;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route as RoutedRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Lorisleiva\Actions\Facades\Actions;

class AppServiceProvider extends ServiceProvider
{
    /** Without this an action's `commandSignature` is never a real command, and the schedule calls a name nothing answers. */
    public function register(): void
    {
        Actions::registerCommands();
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->bindAppointments();
    }

    /** Two tables answer to {appointment}, so the route says which one it means. */
    protected function bindAppointments(): void
    {
        Route::bind('appointment', function (string $value, RoutedRequest $route): Appointment&Model {
            $kind = $route->defaults['appointment_kind'] ?? null;

            abort_unless($kind instanceof AppointmentKind, 404);

            return $kind->model()::query()->findOrFail($value);
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
