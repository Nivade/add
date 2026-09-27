<?php

declare(strict_types=1);

namespace App\Support\Ai\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class AiUnavailable extends RuntimeException
{
    private bool $withoutConsent = false;

    public static function withoutConsent(string $message): self
    {
        $exception = new self($message);
        $exception->withoutConsent = true;

        return $exception;
    }

    /** A question asked synchronously gets a sentence back, never a stack trace. */
    public function render(Request $request): ?JsonResponse
    {
        if (! $request->expectsJson()) {
            return null;
        }

        return new JsonResponse(['message' => $this->withoutConsent
            ? 'Reading this needs AI, which is off. It can be turned on in settings.'
            : 'Reading this needs AI, which is not reachable right now. Try again in a while.',
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
