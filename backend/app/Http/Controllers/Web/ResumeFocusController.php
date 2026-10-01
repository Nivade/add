<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\ResumeSession;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\SessionControlRequest;
use App\Models\ExecutionSession;
use Illuminate\Http\RedirectResponse;

final class ResumeFocusController extends Controller
{
    use ResolvesOwned;

    public function __invoke(SessionControlRequest $request, ExecutionSession $session): RedirectResponse
    {
        ResumeSession::run($this->owned($request, $session), $request->seenEventId());

        return to_route('focus');
    }
}
