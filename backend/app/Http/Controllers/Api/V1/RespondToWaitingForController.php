<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\WaitingFor\RespondToWaitingFor;
use App\Data\WaitingForData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\RespondToWaitingForRequest;
use App\Models\WaitingFor;
use Symfony\Component\HttpFoundation\Response;

final class RespondToWaitingForController extends Controller
{
    use ResolvesOwned;

    public function __invoke(RespondToWaitingForRequest $request, WaitingFor $waitingFor): Response
    {
        $updated = RespondToWaitingFor::run($this->owned($request, $waitingFor), $request->response());

        return WaitingForData::from($updated)->toResponse($request)->setStatusCode(200);
    }
}
