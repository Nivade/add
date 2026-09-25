<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\WaitingFor\CreateWaitingFor;
use App\Data\WaitingForData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWaitingForRequest;

final class StoreWaitingForController extends Controller
{
    public function __invoke(StoreWaitingForRequest $request): WaitingForData
    {
        $user = $this->user($request);

        return WaitingForData::from(CreateWaitingFor::run($user, $request->subject(), $request->note()));
    }
}
