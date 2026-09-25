<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\WaitingFor\RespondToWaitingFor;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\RespondToWaitingForRequest;
use App\Models\WaitingFor;
use Illuminate\Http\RedirectResponse;

final class RespondToWaitingForController extends Controller
{
    use ResolvesOwned;

    public function __invoke(RespondToWaitingForRequest $request, WaitingFor $waitingFor): RedirectResponse
    {
        RespondToWaitingFor::run($this->owned($request, $waitingFor), $request->response());

        return back();
    }
}
