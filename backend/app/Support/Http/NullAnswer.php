<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

/** Nothing to do is an answer, not an error: a cold launch reads these endpoints first. */
final class NullAnswer implements Responsable
{
    public function toResponse($request): JsonResponse
    {
        return new JsonResponse('null', 200, [], 0, json: true);
    }
}
