<?php

declare(strict_types=1);

namespace App\Support\NextAction;

use App\Enums\Place;
use Illuminate\Support\Str;

/** The first rung that separates two candidates decides; the ones below never override it. */
abstract class Rung
{
    abstract public function compare(Candidate $a, Candidate $b, ResolutionContext $context): int;

    public function key(): string
    {
        return Str::snake(class_basename(static::class));
    }

    /** Stated when this rung is what separated the winner from the runner-up. */
    public function decides(Candidate $candidate, ResolutionContext $context): ?string
    {
        return null;
    }

    /** A guess this rung's line states about the person, which they can take back. */
    public function assumes(Candidate $candidate, ResolutionContext $context): ?Place
    {
        return null;
    }

    /** A standing fact about the candidate, true whatever separated it. */
    public function qualifies(Candidate $candidate, ResolutionContext $context): ?string
    {
        return null;
    }
}
