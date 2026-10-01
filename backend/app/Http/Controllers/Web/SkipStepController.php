<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\SkipCurrentStep;
use App\Enums\SessionOutcome;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\StepControlRequest;
use App\Models\ExecutionSession;
use Illuminate\Http\RedirectResponse;

final class SkipStepController extends Controller
{
    use ResolvesOwned;

    public function __invoke(StepControlRequest $request, ExecutionSession $session): RedirectResponse
    {
        $session = SkipCurrentStep::run($this->owned($request, $session), $request->stepId());

        return $session->outcome === SessionOutcome::Completed ? to_route('focus.finished', $session) : back();
    }
}
