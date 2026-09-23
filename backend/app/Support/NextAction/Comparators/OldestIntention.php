<?php

declare(strict_types=1);

namespace App\Support\NextAction\Comparators;

use App\Support\NextAction\Candidate;
use App\Support\NextAction\ResolutionContext;
use App\Support\NextAction\Rung;

final class OldestIntention extends Rung
{
    public function compare(Candidate $a, Candidate $b, ResolutionContext $context): int
    {
        return $a->intention->created_at <=> $b->intention->created_at;
    }

    public function decides(Candidate $candidate, ResolutionContext $context): string
    {
        return 'This one has been waiting the longest.';
    }
}
