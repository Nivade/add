<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sessions\BuildExecutionState;
use App\Actions\Sessions\StartSession;
use App\Concerns\ResolvesStartableStep;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionRequest;
use Symfony\Component\HttpFoundation\Response;

final class StoreSessionController extends Controller
{
    use ResolvesStartableStep;

    public function __invoke(StoreSessionRequest $request): Response
    {
        $step = $this->startableStep($request, $request->string('step_id')->toString());

        $user = $this->user($request);

        $session = StartSession::run($user, $step);

        return BuildExecutionState::run($session)
            ->toResponse($request)
            ->setStatusCode($session->wasRecentlyCreated ? 201 : 200);
    }
}
