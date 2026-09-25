<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sessions\BuildExecutionState;
use App\Actions\Sessions\PauseSession;
use App\Data\ExecutionStateData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Models\ExecutionSession;
use Illuminate\Http\Request;

final class PauseSessionController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, ExecutionSession $session): ExecutionStateData
    {
        return BuildExecutionState::run(PauseSession::run($this->owned($request, $session)));
    }
}
