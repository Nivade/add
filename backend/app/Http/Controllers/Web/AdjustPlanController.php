<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Time\AdjustPlanAssumptions;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustPlanRequest;
use App\Models\CalendarEvent;
use App\Models\Intention;
use Illuminate\Http\RedirectResponse;

final class AdjustPlanController extends Controller
{
    use ResolvesOwned;

    public function __invoke(AdjustPlanRequest $request, Intention|CalendarEvent $appointment): RedirectResponse
    {
        AdjustPlanAssumptions::run(
            $this->owned($request, $appointment),
            $request->minutes(),
        );

        return back();
    }
}
