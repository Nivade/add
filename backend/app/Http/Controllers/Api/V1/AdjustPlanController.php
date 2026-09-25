<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Time\AdjustPlanAssumptions;
use App\Data\BackwardsPlanData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustPlanRequest;
use App\Http\Responses\NullAnswer;
use App\Models\CalendarEvent;
use App\Models\Intention;
use App\Support\Time\BackwardsPlan;

final class AdjustPlanController extends Controller
{
    use ResolvesOwned;

    public function __invoke(AdjustPlanRequest $request, Intention|CalendarEvent $appointment): BackwardsPlanData|NullAnswer
    {
        $appointment = AdjustPlanAssumptions::run(
            $this->owned($request, $appointment),
            $request->minutes(),
        );

        // A plan the day has moved past is null rather than an error: the edit still landed.
        return BackwardsPlan::for($appointment, $this->user($request)->now())
            ?? new NullAnswer;
    }
}
