<?php

declare(strict_types=1);

namespace App\Support\Ai;

use App\Models\User;
use Nvade\AiToolkit\AiRequest;
use Nvade\AiToolkit\Exceptions\AiUnavailable;

/** Without consent the openai driver answers exactly as it would with no key, never a degraded try. */
final class AiConsent
{
    public const string REFUSAL = 'Reading this needs AI, which is off. It can be turned on in settings.';

    public const string UNREACHABLE = 'Reading this needs AI, which is not reachable right now. Try again in a while.';

    public static function refusal(AiRequest $request): ?string
    {
        return User::query()->whereKey($request->rateLimitScope)->value('ai_consented_at') === null ? self::REFUSAL : null;
    }

    /** A question asked synchronously gets one of these sentences back, never the exception's own detail. */
    public static function messageFor(AiUnavailable $exception): string
    {
        return $exception->getMessage() === self::REFUSAL ? self::REFUSAL : self::UNREACHABLE;
    }
}
