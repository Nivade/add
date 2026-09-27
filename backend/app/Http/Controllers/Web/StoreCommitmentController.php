<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Commitments\CreateCommitment;
use App\Enums\CommitmentProvenance;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommitmentRequest;
use Illuminate\Http\RedirectResponse;

final class StoreCommitmentController extends Controller
{
    public function __invoke(StoreCommitmentRequest $request): RedirectResponse
    {
        $user = $this->user($request);

        CreateCommitment::run($user, $request->description(), CommitmentProvenance::UserStated);

        return back();
    }
}
