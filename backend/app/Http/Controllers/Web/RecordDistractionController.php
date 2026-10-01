<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\RecordDistraction;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\SessionControlRequest;
use App\Models\ExecutionSession;
use Illuminate\Http\RedirectResponse;

final class RecordDistractionController extends Controller
{
    use ResolvesOwned;

    public function __invoke(SessionControlRequest $request, ExecutionSession $session): RedirectResponse
    {
        RecordDistraction::run($this->owned($request, $session), $request->seenEventId());

        return back();
    }
}
