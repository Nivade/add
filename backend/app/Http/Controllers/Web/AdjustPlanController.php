<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Intentions\AdjustPlanAssumptions;
use App\Concerns\ResolvesOwnedIntention;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustPlanRequest;
use App\Models\Intention;
use Illuminate\Http\RedirectResponse;

final class AdjustPlanController extends Controller
{
    use ResolvesOwnedIntention;

    public function __invoke(AdjustPlanRequest $request, Intention $intention): RedirectResponse
    {
        AdjustPlanAssumptions::run(
            $this->ownedIntention($request, $intention),
            $request->minutes(),
        );

        return back();
    }
}
