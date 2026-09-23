<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\StartSession;
use App\Concerns\ResolvesStartableStep;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionRequest;
use Illuminate\Http\RedirectResponse;

final class StartFocusController extends Controller
{
    use ResolvesStartableStep;

    public function __invoke(StoreSessionRequest $request): RedirectResponse
    {
        $step = $this->startableStep($request, $request->string('step_id')->toString());

        $user = $this->user($request);

        StartSession::run($user, $step);

        return to_route('focus');
    }
}
