<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Commitments\PromoteStepToCommitment;
use App\Data\CommitmentData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Models\ExecutionSession;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PromoteCurrentStepToCommitmentController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, ExecutionSession $session): Response
    {
        $commitment = PromoteStepToCommitment::run($this->owned($request, $session));

        return CommitmentData::from($commitment)
            ->toResponse($request)
            ->setStatusCode($commitment->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
