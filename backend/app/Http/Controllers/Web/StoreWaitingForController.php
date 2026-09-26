<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\WaitingFor\CreateWaitingFor;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWaitingForRequest;
use Illuminate\Http\RedirectResponse;

final class StoreWaitingForController extends Controller
{
    public function __invoke(StoreWaitingForRequest $request): RedirectResponse
    {
        $user = $this->user($request);

        CreateWaitingFor::run($user, $request->subject(), $request->note());

        return back();
    }
}
