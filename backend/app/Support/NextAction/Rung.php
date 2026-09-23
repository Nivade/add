<?php

declare(strict_types=1);

namespace App\Support\NextAction;

/** A rung that says nothing is the common case, so only the ones that speak override. */
abstract class Rung implements StepComparator
{
    abstract public function compare(Candidate $a, Candidate $b, ResolutionContext $context): int;

    public function decides(Candidate $candidate, ResolutionContext $context): ?string
    {
        return null;
    }

    public function qualifies(Candidate $candidate, ResolutionContext $context): ?string
    {
        return null;
    }
}
