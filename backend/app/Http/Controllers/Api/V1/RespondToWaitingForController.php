<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\WaitingFor\RespondToWaitingFor;
use App\Data\WaitingForData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\RespondToWaitingForRequest;
use App\Models\WaitingFor;

final class RespondToWaitingForController extends Controller
{
    use ResolvesOwned;

    public function __invoke(RespondToWaitingForRequest $request, WaitingFor $waitingFor): WaitingForData
    {
        return WaitingForData::from(RespondToWaitingFor::run($this->owned($request, $waitingFor), $request->response()));
    }
}
