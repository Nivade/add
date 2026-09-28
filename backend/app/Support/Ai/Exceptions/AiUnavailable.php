<?php

declare(strict_types=1);

namespace App\Support\Ai\Exceptions;

use App\Attributes\RespondsWith;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

#[RespondsWith(Response::HTTP_SERVICE_UNAVAILABLE)]
final class AiUnavailable extends RuntimeException
{
    public static function withoutConsent(string $detail): self
    {
        return new self($detail, withoutConsent: true);
    }

    /** A question asked synchronously gets one of these sentences back; $detail stays on the previous exception, for Sentry. */
    public function __construct(string $detail, bool $withoutConsent = false)
    {
        parent::__construct(
            $withoutConsent
                ? 'Reading this needs AI, which is off. It can be turned on in settings.'
                : 'Reading this needs AI, which is not reachable right now. Try again in a while.',
            previous: new RuntimeException($detail),
        );
    }
}
