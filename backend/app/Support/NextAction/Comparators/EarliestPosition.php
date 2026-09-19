<?php

declare(strict_types=1);

namespace App\Support\NextAction\Comparators;

use App\Support\NextAction\Candidate;
use App\Support\NextAction\ResolutionContext;
use App\Support\NextAction\StepComparator;

/** The last rung is a total order, so two runs over the same world return the same step. */
final class EarliestPosition implements StepComparator
{
    public function compare(Candidate $a, Candidate $b, ResolutionContext $context): int
    {
        return [$a->step->position, $a->step->id] <=> [$b->step->position, $b->step->id];
    }

    public function decides(Candidate $candidate, ResolutionContext $context): string
    {
        return 'This is simply what comes next.';
    }

    public function qualifies(Candidate $candidate, ResolutionContext $context): ?string
    {
        return null;
    }
}
