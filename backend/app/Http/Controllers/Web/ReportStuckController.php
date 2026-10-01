<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\ReportStuck;
use App\Enums\SessionOutcome;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportStuckRequest;
use App\Models\ExecutionSession;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class ReportStuckController extends Controller
{
    use ResolvesOwned;

    public function __invoke(ReportStuckRequest $request, ExecutionSession $session): RedirectResponse
    {
        $session = ReportStuck::run(
            $this->owned($request, $session),
            $request->stepId(),
            $request->reason(),
            $request->note(),
        );

        return match ($session->outcome) {
            SessionOutcome::Completed => to_route('focus.finished', $session),
            SessionOutcome::Stopped => $this->stoppedForNow(),
            default => back(),
        };
    }

    private function stoppedForNow(): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stopped for now. It will be here later.')]);

        return to_route('home');
    }
}
