<?php

declare(strict_types=1);

namespace App\Support\NextAction;

use App\Support\NextAction\Comparators\NotRecentlySkipped;

/** One definition of "smallest": what overwhelm mode offers and what a step too big is traded for. */
final class SmallestFirst
{
    /**
     * @param  list<Candidate>  $candidates
     * @return list<Candidate>
     */
    public static function sort(array $candidates, ResolutionContext $context): array
    {
        $coolOff = new NotRecentlySkipped;

        usort($candidates, fn (Candidate $a, Candidate $b): int => $coolOff->compare($a, $b, $context)
            ?: self::size($a) <=> self::size($b));

        return $candidates;
    }

    /**
     * An unestimated step loses a tie to a timed one of the same assumed cost.
     *
     * @return array{int, bool, int, string}
     */
    private static function size(Candidate $candidate): array
    {
        return [
            $candidate->cost(),
            $candidate->estimatedSeconds() === null,
            $candidate->step->position,
            $candidate->step->id,
        ];
    }
}
