<?php

declare(strict_types=1);

namespace App\Support\NextAction;

use App\Models\Intention;
use App\Models\Step;
use App\Support\Time\EstimateWords;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class Candidate
{
    /** A step nobody estimated is assumed to cost this much when planning backwards. */
    private const int UNESTIMATED_SECONDS = 900;

    /** Work fits "only just" when doing it at half speed would still run past the deadline. */
    private const int FIT_SLACK = 2;

    public function __construct(
        public Step $step,
        public Intention $intention,
        public int $remainingSeconds,
    ) {}

    public function estimatedSeconds(): ?int
    {
        return $this->step->estimated_seconds;
    }

    public function cost(): int
    {
        return self::costOf($this->step);
    }

    public static function costOf(Step $step): int
    {
        return $step->estimated_seconds ?? self::UNESTIMATED_SECONDS;
    }

    public function deadlineAt(): ?CarbonImmutable
    {
        return $this->intention->deadline_at;
    }

    public function deadlineInWords(CarbonImmutable $now): ?string
    {
        return $this->deadlineAt()?->setTimezone($now->getTimezone())->diffForHumans([
            'other' => $now,
            'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW,
        ]);
    }

    public function estimateInWords(): ?string
    {
        return EstimateWords::for($this->estimatedSeconds());
    }

    public function deadlinePassed(CarbonImmutable $now): bool
    {
        $deadline = $this->deadlineAt();

        return $deadline instanceof CarbonImmutable && $deadline <= $now;
    }

    /** A deadline already behind us is not a reach: there is no stretch of day left to fit the work into. */
    public function onlyJustFits(CarbonImmutable $now): bool
    {
        $deadline = $this->deadlineAt();

        return $deadline instanceof CarbonImmutable
            && ! $this->deadlinePassed($now)
            && $now->addSeconds($this->remainingSeconds * self::FIT_SLACK) >= $deadline;
    }
}
