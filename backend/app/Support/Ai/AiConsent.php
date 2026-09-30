<?php

declare(strict_types=1);

namespace App\Support\Ai;

use App\Exceptions\AiConsentRequired;
use App\Models\User;
use Nvade\AiToolkit\AiRequest;
use Nvade\AiToolkit\Contracts\AiProvider;
use Nvade\AiToolkit\Events\AiRequestFailed;

/** Without consent a driver that leaves the machine answers exactly as it would with no key, never a degraded try. */
final class AiConsent
{
    /** Unknown drivers count as leaving, so a new live driver is gated before anyone remembers to list it. */
    public static function leavesTheMachine(AiProvider $provider): bool
    {
        return match ($provider->name()) {
            'canned', 'fake', 'null' => false,
            // A recording fixture driver asks a live driver on a miss, and the toolkit builds that one unwrapped.
            'fixture' => str_starts_with((string) config('ai-toolkit.fixture.on_miss'), 'record:'),
            default => true,
        };
    }

    /** The toolkit's dispatcher sits inside the gate, so a refusal fires its failure event from here. */
    public static function ensureConsented(AiRequest $request, string $provider): void
    {
        if (User::query()->find($request->rateLimitScope)?->hasConsentedToAi() === true) {
            return;
        }

        $refusal = new AiConsentRequired("No AI consent on record for {$request->operation}.");

        event(new AiRequestFailed($request, $refusal, $provider, 0));

        throw $refusal;
    }
}
