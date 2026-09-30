<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Ai\RunCaptureEval;
use App\Actions\Ai\RunDecompositionEval;
use App\Attributes\Driver;
use App\Support\Ai\AiConsent;
use App\Support\Ai\Providers\CannedAiProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Nvade\AiToolkit\AiProviderManager;
use Nvade\AiToolkit\AiRequest;
use Nvade\AiToolkit\Contracts\AiProvider;
use Nvade\AiToolkit\Providers\DispatchingAiProvider;
use Nvade\AiToolkit\Providers\GatedAiProvider;
use Nvade\AiToolkit\Providers\OpenAiProvider;

final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The corpus is nobody's words, so the evals skip the consent gate; the dispatcher keeps each call in the log.
        $this->app->when([RunCaptureEval::class, RunDecompositionEval::class])
            ->needs(AiProvider::class)
            ->give(static fn (Application $app): AiProvider => new DispatchingAiProvider($app->make(OpenAiProvider::class), $app));
    }

    public function boot(): void
    {
        $this->callAfterResolving(AiProviderManager::class, static function (AiProviderManager $manager): void {
            $manager->extend(Driver::nameOf(CannedAiProvider::class), fn (): CannedAiProvider => new CannedAiProvider);

            // Choosing a live driver is the deployment's consent; a person's own consent is still gated per call.
            $manager->wrap(static fn (AiProvider $provider): AiProvider => AiConsent::leavesTheMachine($provider)
                ? new GatedAiProvider($provider, static function (AiRequest $request) use ($provider): ?string {
                    AiConsent::ensureConsented($request, $provider->name());

                    return null;
                })
                : $provider);
        });
    }
}
