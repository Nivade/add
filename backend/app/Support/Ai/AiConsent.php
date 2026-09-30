<?php

declare(strict_types=1);

namespace App\Support\Ai;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Nvade\AiToolkit\AiRequest;
use Nvade\AiToolkit\Contracts\AiProvider;
use Nvade\AiToolkit\Exceptions\AiUnavailable;

/** Without consent a driver that leaves the machine answers exactly as it would with no key, never a degraded try. */
final class AiConsent
{
    public const string REFUSAL = 'Reading this needs AI, which is off. It can be turned on in settings.';

    /** A recording fixture driver asks a live driver on a miss, and the toolkit builds that one unwrapped. */
    public static function leavesTheMachine(AiProvider $provider): bool
    {
        return $provider->name() === 'openai'
            || ($provider->name() === 'fixture' && str_starts_with((string) config('ai-toolkit.fixture.on_miss'), 'record:'));
    }

    public static function refusal(AiRequest $request, string $provider): ?string
    {
        if (User::query()->find($request->rateLimitScope)?->hasConsentedToAi() === true) {
            return null;
        }

        self::logRefusal($request, $provider);

        return self::REFUSAL;
    }

    private static function logRefusal(AiRequest $request, string $provider): void
    {
        $channel = config('ai-toolkit.log.channel');

        if (! is_string($channel) || $channel === '') {
            return;
        }

        Log::channel($channel)->warning('AI call refused', [
            'operation' => $request->operation,
            'provider' => $provider,
            'prompt_version' => $request->promptVersion,
            'schema_version' => $request->schemaVersion,
            'exception' => AiUnavailable::class,
        ]);
    }
}
