<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sessions\StartSession;
use App\Data\ExecutionStateData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSessionRequest;
use App\Models\Step;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

final class StoreSessionController extends Controller
{
    public function __invoke(StoreSessionRequest $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $step = Step::query()->findOrFail($request->string('step_id')->toString());

        abort_unless($step->intention->user_id === $user->id, 404);

        $session = StartSession::run($user, $step);

        return ExecutionStateData::of($session)
            ->toResponse($request)
            ->setStatusCode($session->wasRecentlyCreated ? 201 : 200);
    }
}
