<?php

declare(strict_types=1);

namespace App\Support\NextAction\Comparators;

use App\Support\NextAction\Candidate;
use App\Support\NextAction\ResolutionContext;
use App\Support\NextAction\StepComparator;
use Carbon\CarbonImmutable;

final class HasDeadline implements StepComparator
{
    public function compare(Candidate $a, Candidate $b, ResolutionContext $context): int
    {
        return ($b->deadlineAt() instanceof CarbonImmutable) <=> ($a->deadlineAt() instanceof CarbonImmutable);
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

        return "Your deadline is {$deadline}.";
    }
}
