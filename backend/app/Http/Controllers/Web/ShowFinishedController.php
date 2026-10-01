<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\BuildFinished;
use App\Enums\SessionOutcome;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Models\ExecutionSession;
use Illuminate\Http\Request;
use Inertia\Response;

final class ShowFinishedController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, ExecutionSession $session): Response
    {
        abort_unless($this->owned($request, $session)->outcome === SessionOutcome::Completed, 404);

        return inertia('finished', ['finished' => BuildFinished::run($session)]);
    }
}
