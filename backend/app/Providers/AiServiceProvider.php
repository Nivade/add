<?php

declare(strict_types=1);

namespace App\Providers;

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
        $this->app->singleton(AiProvider::class, fn (): AiProvider => new LoggingAiProvider(match (config('ai.driver')) {
            // Choosing this driver is the deployment's consent; a person's own consent is still gated per call.
            'openai' => new ConsentGatedAiProvider(new OpenAiProvider),
            'canned' => new CannedAiProvider,
            'fixture' => new FixtureAiProvider,
            'fake' => new FakeAiProvider,
            default => new NullAiProvider,
        }));
    }
}
