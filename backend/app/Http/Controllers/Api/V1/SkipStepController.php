<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sessions\BuildExecutionState;
use App\Actions\Sessions\SkipCurrentStep;
use App\Data\ExecutionStateData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\StepControlRequest;
use App\Models\ExecutionSession;

final class SkipStepController extends Controller
{
    use ResolvesOwned;

    public function __invoke(StepControlRequest $request, ExecutionSession $session): ExecutionStateData
    {
        return BuildExecutionState::run(SkipCurrentStep::run($this->owned($request, $session), $request->stepId()));
    }
}
