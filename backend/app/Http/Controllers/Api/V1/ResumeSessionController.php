<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sessions\BuildExecutionState;
use App\Actions\Sessions\ResumeSession;
use App\Data\ExecutionStateData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\SessionControlRequest;
use App\Models\ExecutionSession;

final class ResumeSessionController extends Controller
{
    use ResolvesOwned;

    public function __invoke(SessionControlRequest $request, ExecutionSession $session): ExecutionStateData
    {
        return BuildExecutionState::run(ResumeSession::run($this->owned($request, $session), $request->seenEventId()));
    }
}
