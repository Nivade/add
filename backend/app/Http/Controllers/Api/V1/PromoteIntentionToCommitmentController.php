<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Commitments\PromoteIntentionToCommitment;
use App\Data\CommitmentData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Models\Intention;
use Illuminate\Http\Request;

final class PromoteIntentionToCommitmentController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, Intention $intention): CommitmentData
    {
        return CommitmentData::from(PromoteIntentionToCommitment::run($this->owned($request, $intention)));
    }
}
