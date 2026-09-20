<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sessions\StartSession;
use App\Concerns\ResolvesStartableStep;
use App\Data\ExecutionStateData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionRequest;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

final class StoreSessionController extends Controller
{
    use ResolvesStartableStep;

    public function __invoke(StoreSessionRequest $request): Response
    {
        $step = $this->startableStep($request, $request->string('step_id')->toString());

        /** @var User $user */
        $user = $request->user();

        $session = StartSession::run($user, $step);

        return ExecutionStateData::of($session)
            ->toResponse($request)
            ->setStatusCode($session->wasRecentlyCreated ? 201 : 200);
    }
}
