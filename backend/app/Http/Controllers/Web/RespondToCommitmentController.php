<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Commitments\RespondToCommitment;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\RespondToCommitmentRequest;
use App\Models\Commitment;
use Illuminate\Http\RedirectResponse;

final class RespondToCommitmentController extends Controller
{
    use ResolvesOwned;

    public function __invoke(RespondToCommitmentRequest $request, Commitment $commitment): RedirectResponse
    {
        RespondToCommitment::run($this->owned($request, $commitment), $request->response());

        return back();
    }
}
