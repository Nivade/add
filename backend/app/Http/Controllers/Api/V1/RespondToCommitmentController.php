<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Commitments\RespondToCommitment;
use App\Data\CommitmentData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\RespondToCommitmentRequest;
use App\Models\Commitment;
use Symfony\Component\HttpFoundation\Response;

final class RespondToCommitmentController extends Controller
{
    use ResolvesOwned;

    public function __invoke(RespondToCommitmentRequest $request, Commitment $commitment): Response
    {
        $updated = RespondToCommitment::run($this->owned($request, $commitment), $request->response());

        return CommitmentData::from($updated)->toResponse($request)->setStatusCode(200);
    }
}
