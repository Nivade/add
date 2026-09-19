<?php

declare(strict_types=1);

namespace App\Support\Time;

use App\Data\BackwardsPlanData;
use App\Data\PlanRungData;
use App\Enums\PlanRung;
use App\Models\Intention;
use Carbon\CarbonImmutable;

/** Arithmetic, never a model: a travel-time guess presented as fact is how the app starts lying. */
final class BackwardsPlan
{
    public static function for(Intention $intention, CarbonImmutable $now): ?BackwardsPlanData
    {
        $deadline = $intention->deadline_at?->setTimezone($now->getTimezone());

        if (! $deadline instanceof CarbonImmutable || ! $deadline->isSameDay($now)) {
            return null;
        }

        $at = $deadline;
        $rungs = [];

        foreach ([PlanRung::Leave, PlanRung::GetReady, PlanRung::FindThings] as $rung) {
            $stated = $intention->getAttribute($rung->column());
            $seconds = is_int($stated) ? $stated : $rung->assumedSeconds();
            $at = $at->subSeconds($seconds);

            $rungs[] = new PlanRungData(
                $rung,
                $at->toIso8601String(),
                $at->format('H:i'),
                $seconds,
                ! is_int($stated),
                $at < $now,
            );
        }

        return new BackwardsPlanData(
            $intention->id,
            $deadline->format('H:i'),
            array_reverse($rungs),
        );
    }
}
