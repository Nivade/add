<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidCommitmentResponse extends RuntimeException implements ShouldntReport
{
    /** Answering a commitment that is already settled is an out-of-date client, not a bug. */
    public function render(Request $request): ?JsonResponse
    {
        return $request->expectsJson()
            ? new JsonResponse(['message' => $this->getMessage()], Response::HTTP_CONFLICT)
            : null;
    }
}
