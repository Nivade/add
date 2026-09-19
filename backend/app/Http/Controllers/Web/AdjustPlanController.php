<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Time\AdjustPlanAssumptions;
use App\Concerns\ResolvesOwnedAppointment;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustPlanRequest;
use App\Models\CalendarEvent;
use App\Models\Intention;
use Illuminate\Http\RedirectResponse;

final class AdjustPlanController extends Controller
{
    use ResolvesOwnedAppointment;

    public function __invoke(AdjustPlanRequest $request, Intention|CalendarEvent $appointment): RedirectResponse
    {
        AdjustPlanAssumptions::run(
            $this->ownedAppointment($request, $appointment),
            $request->minutes(),
        );

        return back();
    }
}
