<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sessions\BuildExecutionState;
use App\Data\ExecutionStateData;
use App\Http\Controllers\Controller;
use App\Http\Responses\NullAnswer;
use App\Models\ExecutionSession;
use Illuminate\Http\Request;

final class ShowCurrentSessionController extends Controller
{
    public function __invoke(Request $request): ExecutionStateData|NullAnswer
    {
        $user = $this->user($request);

        $session = $user->runningSession()->getResults();

        return $session instanceof ExecutionSession
            ? BuildExecutionState::run($session)
            : new NullAnswer;
    }
}
