<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Commitments\PromoteStepToCommitment;
use App\Data\CommitmentData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\StepControlRequest;
use App\Models\ExecutionSession;
use Symfony\Component\HttpFoundation\Response;

final class PromoteCurrentStepToCommitmentController extends Controller
{
    use ResolvesOwned;

    public function __invoke(StepControlRequest $request, ExecutionSession $session): Response
    {
        $commitment = PromoteStepToCommitment::run($this->owned($request, $session), $request->stepId());

        return CommitmentData::from($commitment)
            ->toResponse($request)
            ->setStatusCode($commitment->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
