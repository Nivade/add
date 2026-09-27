<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/** A client asking for a change that no longer applies is out of date, not broken. */
abstract class OutOfDateTransition extends RuntimeException implements ShouldntReport
{
    public function render(Request $request): ?JsonResponse
    {
        return $request->expectsJson()
            ? new JsonResponse(['message' => $this->getMessage()], Response::HTTP_CONFLICT)
            : null;
    }
}
