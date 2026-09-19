<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Time\AdjustPlanAssumptions;
use App\Concerns\ResolvesOwnedAppointment;
use App\Data\BackwardsPlanData;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustPlanRequest;
use App\Models\CalendarEvent;
use App\Models\Intention;
use App\Models\User;
use App\Support\Time\BackwardsPlan;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

final class AdjustPlanController extends Controller
{
    use ResolvesOwnedAppointment;

    public function __invoke(AdjustPlanRequest $request, Intention|CalendarEvent $appointment): BackwardsPlanData|JsonResponse
    {
        $appointment = AdjustPlanAssumptions::run(
            $this->ownedAppointment($request, $appointment),
            $request->minutes(),
        );

        /** @var User $user */
        $user = $request->user();

        // A plan the day has moved past is null rather than an error: the edit still landed.
        return BackwardsPlan::for($appointment, CarbonImmutable::now($user->timezone))
            ?? new JsonResponse('null', 200, [], 0, json: true);
    }
}
