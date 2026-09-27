<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Commitments\CreateCommitment;
use App\Data\CommitmentData;
use App\Enums\CommitmentProvenance;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommitmentRequest;

final class StoreCommitmentController extends Controller
{
    public function __invoke(StoreCommitmentRequest $request): CommitmentData
    {
        $user = $this->user($request);

        return CommitmentData::from(CreateCommitment::run($user, $request->description(), CommitmentProvenance::UserStated));
    }
}
