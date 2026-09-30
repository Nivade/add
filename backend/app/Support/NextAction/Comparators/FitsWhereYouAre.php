<?php

declare(strict_types=1);

namespace App\Support\NextAction\Comparators;

use App\Enums\Place;
use App\Support\NextAction\Candidate;
use App\Support\NextAction\ResolutionContext;
use App\Support\NextAction\Rung;

/** Reorders and never filters: a wrong guess that hid a step could hide the only thing that needed doing. */
final class FitsWhereYouAre extends Rung
{
    public function compare(Candidate $a, Candidate $b, ResolutionContext $context): int
    {
        return $context->whereabouts->fit($b->step->place) <=> $context->whereabouts->fit($a->step->place);
    }

    public function decides(Candidate $candidate, ResolutionContext $context): string
    {
        return $this->qualifies($candidate, $context) ?? ($candidate->step->place instanceof Place
            ? 'The others need you somewhere you seem not to be.'
            : 'This one does not depend on where you are.');
    }

    public function qualifies(Candidate $candidate, ResolutionContext $context): ?string
    {
        return $this->assumedPlace($candidate, $context)?->seemsHere();
    }

    /** The guess the why states whenever this step fits, and so the one the person can take back. */
    public function assumedPlace(Candidate $candidate, ResolutionContext $context): ?Place
    {
        $place = $candidate->step->place;

        return $place instanceof Place && in_array($place, $context->whereabouts->likely, true) ? $place : null;
    }
}
