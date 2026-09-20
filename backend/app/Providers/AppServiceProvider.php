<?php

namespace App\Providers;

use App\Contracts\Appointment;
use App\Models\Intention;
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
    /**
     * Register any application services.
     */
    /** Without this an action's `commandSignature` is never a real command, and the schedule calls a name nothing answers. */
    public function register(): void
    {
        Actions::registerCommands();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->bindAppointments();
    }

    /** Two tables answer to {appointment}, so the route says which one it means. */
    protected function bindAppointments(): void
    {
        Route::bind('appointment', function (string $value, RoutedRequest $route): Appointment&Model {
            /** @var class-string<Appointment&Model> $model */
            $model = $route->defaults['appointment_model'] ?? Intention::class;

            return $model::query()->findOrFail($value);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
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
