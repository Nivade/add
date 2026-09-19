<?php

declare(strict_types=1);

namespace App\Support\NextAction\Comparators;

use App\Support\NextAction\Candidate;
use App\Support\NextAction\ResolutionContext;
use App\Support\NextAction\StepComparator;

final class StartableNow implements StepComparator
{
    public function compare(Candidate $a, Candidate $b, ResolutionContext $context): int
    {
        return $a->cost() <=> $b->cost();
    }

    public function decides(Candidate $candidate, ResolutionContext $context): ?string
    {
        return $this->qualifies($candidate, $context);
    }

    public function qualifies(Candidate $candidate, ResolutionContext $context): ?string
    {
        $estimate = $candidate->estimateInWords();

        return $estimate === null ? null : "This takes about {$estimate}.";
    }
}
