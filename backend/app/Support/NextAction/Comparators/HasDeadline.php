<?php

declare(strict_types=1);

namespace App\Support\NextAction\Comparators;

use App\Support\NextAction\Candidate;
use App\Support\NextAction\ResolutionContext;
use App\Support\NextAction\Rung;
use Carbon\CarbonImmutable;

final class HasDeadline extends Rung
{
    public function compare(Candidate $a, Candidate $b, ResolutionContext $context): int
    {
        $verdict = ($b->deadlineAt() instanceof CarbonImmutable) <=> ($a->deadlineAt() instanceof CarbonImmutable);

        if ($verdict !== 0 || ! $a->deadlineAt() instanceof CarbonImmutable) {
            return $verdict;
        }

        // Both are dated, so the soonest real constraint leads.
        return $a->deadlineAt() <=> $b->deadlineAt();
    }

    public function decides(Candidate $candidate, ResolutionContext $context): ?string
    {
        return $this->qualifies($candidate, $context);
    }

    public function qualifies(Candidate $candidate, ResolutionContext $context): ?string
    {
        $deadline = $candidate->deadlineInWords($context->now);

        if ($deadline === null || $candidate->onlyJustFits($context->now)) {
            return null;
        }

        // Past tense, stated flatly: the date moved, which is a fact about the day and not about them.
        return $candidate->deadlinePassed($context->now)
            ? "Your deadline was {$deadline}."
            : "Your deadline is {$deadline}.";
    }
}
