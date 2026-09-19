<?php

declare(strict_types=1);

namespace App\Actions\Intentions;

use App\Enums\PlanRung;
use App\Models\Intention;
use Lorisleiva\Actions\Concerns\AsObject;

/** §13 says the assumptions are editable, so a stated minute count replaces the assumed one. */
final class AdjustPlanAssumptions
{
    use AsObject;

    /** @param  array<string, int|null>  $minutes  keyed by `PlanRung` value; null returns the rung to its assumption */
    public function handle(Intention $intention, array $minutes): Intention
    {
        foreach (PlanRung::cases() as $rung) {
            if (! array_key_exists($rung->value, $minutes)) {
                continue;
            }

            $stated = $minutes[$rung->value];

            $intention->setAttribute($rung->column(), $stated === null ? null : $stated * 60);
        }

        $intention->save();

        return $intention;
    }
}
