<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Ai\AiConsent;
use App\Support\Ai\Providers\CannedAiProvider;
use Illuminate\Support\ServiceProvider;
use Nvade\AiToolkit\Contracts\AiProvider;
use Nvade\AiToolkit\Facades\AiToolkit;
use Nvade\AiToolkit\Providers\GatedAiProvider;

final class AiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        AiToolkit::extend('canned', fn (): CannedAiProvider => new CannedAiProvider);

        // Choosing the openai driver is the deployment's consent; a person's own consent is still gated per call.
        AiToolkit::wrap(fn (AiProvider $provider): AiProvider => $provider->name() === 'openai'
            ? new GatedAiProvider($provider, AiConsent::refusal(...))
            : $provider);
    }
}
