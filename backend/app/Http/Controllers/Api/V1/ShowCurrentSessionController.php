<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\ExecutionStateData;
use App\Http\Controllers\Controller;
use App\Models\ExecutionSession;
use App\Models\User;
use App\Support\Execution\RunningSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShowCurrentSessionController extends Controller
{
    public function __invoke(Request $request): ExecutionStateData|JsonResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $session = RunningSession::forUser($user);

        // No open session is an answer, not an error: a cold launch reads this first.
        return $session instanceof ExecutionSession
            ? ExecutionStateData::of($session)
            : new JsonResponse('null', 200, [], 0, json: true);
    }
}
