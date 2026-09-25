<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sessions\BuildExecutionState;
use App\Actions\Sessions\ReportStuck;
use App\Data\ExecutionStateData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportStuckRequest;
use App\Models\ExecutionSession;

final class ReportStuckController extends Controller
{
    use ResolvesOwned;

    public function __invoke(ReportStuckRequest $request, ExecutionSession $session): ExecutionStateData
    {
        return BuildExecutionState::run(ReportStuck::run(
            $this->owned($request, $session),
            $request->reason(),
            $request->note(),
        ));
    }
}
