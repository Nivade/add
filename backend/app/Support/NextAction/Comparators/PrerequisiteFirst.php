<?php

declare(strict_types=1);

namespace App\Support\NextAction\Comparators;

use App\Support\NextAction\Candidate;
use App\Support\NextAction\ResolutionContext;
use App\Support\NextAction\StepComparator;

/** The decomposer was asked for a sequence, so inside one intention the order it gave holds. */
final class PrerequisiteFirst implements StepComparator
{
    public function compare(Candidate $a, Candidate $b, ResolutionContext $context): int
    {
        if ($a->intention->id !== $b->intention->id) {
            return 0;
        }

        return $a->step->position <=> $b->step->position;
    }

    public function decides(Candidate $candidate, ResolutionContext $context): string
    {
        return 'It is the first thing left in this one.';
    }

    public function qualifies(Candidate $candidate, ResolutionContext $context): ?string
    {
        return null;
    }
}
