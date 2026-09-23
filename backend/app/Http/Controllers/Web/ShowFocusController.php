<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Data\ExecutionStateData;
use App\Http\Controllers\Controller;
use App\Models\ExecutionSession;
use App\Support\Execution\RunningSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final class ShowFocusController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $this->user($request);

        $session = RunningSession::forUser($user);

        // Focus without a session is not a screen; home is where the next thing is chosen.
        if (! $session instanceof ExecutionSession) {
            return to_route('home');
        }

        return inertia('focus', ['state' => ExecutionStateData::of($session)]);
    }
}
