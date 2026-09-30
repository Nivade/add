<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\CompleteStep;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\StepControlRequest;
use App\Models\ExecutionSession;
use Illuminate\Http\RedirectResponse;

final class CompleteStepController extends Controller
{
    use ResolvesOwned;

    public function __invoke(StepControlRequest $request, ExecutionSession $session): RedirectResponse
    {
        CompleteStep::run($this->owned($request, $session), $request->stepId());

        return back();
    }
}
