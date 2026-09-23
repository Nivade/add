<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\ExecutionStateData;
use App\Http\Controllers\Controller;
use App\Models\ExecutionSession;
use App\Support\Execution\RunningSession;
use App\Support\Http\NullAnswer;
use Illuminate\Http\Request;

final class ShowCurrentSessionController extends Controller
{
    public function __invoke(Request $request): ExecutionStateData|NullAnswer
    {
        $user = $this->user($request);

        $session = RunningSession::forUser($user);

        return $session instanceof ExecutionSession
            ? ExecutionStateData::of($session)
            : new NullAnswer;
    }
}
