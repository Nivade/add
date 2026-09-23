<?php

declare(strict_types=1);

namespace App\Support\Execution\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidSessionTransition extends RuntimeException implements ShouldntReport
{
    /** A client asking a landed session to move is out of date, not broken. */
    public function render(Request $request): ?JsonResponse
    {
        return $request->expectsJson()
            ? new JsonResponse(['message' => $this->getMessage()], Response::HTTP_CONFLICT)
            : null;
    }
}
