<?php

declare(strict_types=1);

namespace App\Data\Concerns;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** For Data that a POST returns without having created it, where laravel-data would answer 201. */
trait AnswersOk
{
    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
