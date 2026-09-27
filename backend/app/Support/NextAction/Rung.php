<?php

declare(strict_types=1);

namespace App\Support\NextAction;

/** The first rung that separates two candidates decides; the ones below never override it. */
abstract class Rung
{
    abstract public function compare(Candidate $a, Candidate $b, ResolutionContext $context): int;

    /** Stated when this rung is what separated the winner from the runner-up. */
    public function decides(Candidate $candidate, ResolutionContext $context): ?string
    {
        return null;
    }

    /** A standing fact about the candidate, true whatever separated it. */
    public function qualifies(Candidate $candidate, ResolutionContext $context): ?string
    {
        return null;
    }
}
