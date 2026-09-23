<?php

declare(strict_types=1);

namespace App\Support\NextAction\Comparators;

use App\Support\NextAction\Candidate;
use App\Support\NextAction\ResolutionContext;
use App\Support\NextAction\Rung;

final class DeadlineWithinReach extends Rung
{
    public function compare(Candidate $a, Candidate $b, ResolutionContext $context): int
    {
        return $b->onlyJustFits($context->now) <=> $a->onlyJustFits($context->now);
    }

    public function decides(Candidate $candidate, ResolutionContext $context): ?string
    {
        $deadline = $candidate->deadlineInWords($context->now);

        return $deadline === null
            ? null
            : "Your deadline is {$deadline} and what is left only just fits.";
    }
}
