<?php

declare(strict_types=1);

namespace App\Providers;

use App\Attributes\Driver;
use App\Contracts\AiProvider;
use App\Support\Ai\Providers\CannedAiProvider;
use App\Support\Ai\Providers\ConsentGatedAiProvider;
use App\Support\Ai\Providers\FakeAiProvider;
use App\Support\Ai\Providers\FixtureAiProvider;
use App\Support\Ai\Providers\LoggingAiProvider;
use App\Support\Ai\Providers\NullAiProvider;
use App\Support\Ai\Providers\OpenAiProvider;
use Illuminate\Support\ServiceProvider;

final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton so a test can push answers into the fake and the action resolves the same one.
        $this->app->singleton(function (): AiProvider {
            $class = Driver::classFor((string) config('ai.driver'), [
                OpenAiProvider::class,
                CannedAiProvider::class,
                FixtureAiProvider::class,
                FakeAiProvider::class,
            ]);

            // Choosing the openai driver is the deployment's consent; a person's own consent is still gated per call.
            $driver = match ($class) {
                null => new NullAiProvider,
                OpenAiProvider::class => new ConsentGatedAiProvider($this->app->make(OpenAiProvider::class)),
                default => $this->app->make($class),
            };

            return new LoggingAiProvider($driver);
        });
    }
}
