<?php

declare(strict_types=1);

namespace App\Actions\Time;

use App\Contracts\Appointment;
use App\Enums\PlanRung;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsObject;

/** §13 says the assumptions are editable, so a stated minute count replaces the assumed one. */
final class AdjustPlanAssumptions
{
    use AsObject;

    /** @param  array<string, int|null>  $minutes  keyed by `PlanRung` value; null returns the rung to its assumption */
    public function handle(Appointment&Model $appointment, array $minutes): Appointment&Model
    {
        foreach (PlanRung::cases() as $rung) {
            if (! array_key_exists($rung->value, $minutes)) {
                continue;
            }

            $stated = $minutes[$rung->value];

            $appointment->stateSeconds($rung, $stated === null ? null : $stated * 60);
        }

        $appointment->save();

        return $appointment;
    }
}
