<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Commitments\PromoteIntentionToCommitment;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Models\Intention;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PromoteIntentionToCommitmentController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, Intention $intention): RedirectResponse
    {
        PromoteIntentionToCommitment::run($this->owned($request, $intention));

        return back();
    }
}
