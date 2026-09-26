<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidIntentionTransition extends RuntimeException implements ShouldntReport
{
    /** A client asking for a transition that no longer applies is out of date, not broken. */
    public function render(Request $request): ?JsonResponse
    {
        return $request->expectsJson()
            ? new JsonResponse(['message' => $this->getMessage()], Response::HTTP_CONFLICT)
            : null;
    }
}
