<?php

declare(strict_types=1);

namespace App\Support\NextAction\Comparators;

use App\Support\NextAction\Candidate;
use App\Support\NextAction\ResolutionContext;
use App\Support\NextAction\Rung;

/** A cool-off, not a penalty: the step comes back at full standing, and the person is never told. */
final class NotRecentlySkipped extends Rung
{
    /** Long enough to stop re-offering the same thing, short enough that it returns the same day. */
    private const int COOL_OFF_HOURS = 4;

    public function compare(Candidate $a, Candidate $b, ResolutionContext $context): int
    {
        return $this->coolingOff($a, $context) <=> $this->coolingOff($b, $context);
    }

    private function coolingOff(Candidate $candidate, ResolutionContext $context): bool
    {
        $skippedAt = $candidate->step->last_skipped_at;

        return $skippedAt !== null && $skippedAt->addHours(self::COOL_OFF_HOURS) > $context->now;
    }
}
