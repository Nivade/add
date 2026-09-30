<?php

declare(strict_types=1);

namespace App\Support\NextAction;

use App\Contracts\NextActionResolver;
use App\Data\IntentionData;
use App\Data\NextActionData;
use App\Data\StepData;
use App\Enums\Place;
use App\Models\ExecutionSession;
use App\Models\User;
use App\Support\NextAction\Comparators\DeadlineWithinReach;
use App\Support\NextAction\Comparators\EarliestPosition;
use App\Support\NextAction\Comparators\FitsWhereYouAre;
use App\Support\NextAction\Comparators\HasDeadline;
use App\Support\NextAction\Comparators\NotRecentlySkipped;
use App\Support\NextAction\Comparators\OldestIntention;
use App\Support\NextAction\Comparators\PrerequisiteFirst;
use App\Support\NextAction\Comparators\StartableNow;

/** No score: a number six inputs went into cannot be explained, and the `why` has to be. */
final class ChainedNextActionResolver implements NextActionResolver
{
    /** @var list<Rung> */
    private array $chain;

    private FitsWhereYouAre $fitsWhereYouAre;

    public function __construct()
    {
        $this->fitsWhereYouAre = new FitsWhereYouAre;

        $this->chain = [
            new DeadlineWithinReach,
            $this->fitsWhereYouAre,
            new HasDeadline,
            new NotRecentlySkipped,
            new PrerequisiteFirst,
            new StartableNow,
            new OldestIntention,
            new EarliestPosition,
        ];
    }

    public function resolve(User $user, ResolutionContext $context): ?NextActionData
    {
        $candidates = CandidatePool::forUser($user);

        if ($candidates === []) {
            return null;
        }

        $continued = $this->continuation($user, $candidates);

        if ($continued instanceof Candidate) {
            return $this->answer($continued, ['You are part-way through this one.'], null);
        }

        usort($candidates, fn (Candidate $a, Candidate $b): int => $this->rank($a, $b, $context));

        $winner = $candidates[0];
        $separator = $this->separator($candidates, $context);
        $assumedPlace = $this->speaks($this->fitsWhereYouAre, $separator) ? $this->fitsWhereYouAre->assumedPlace($winner, $context) : null;

        return $this->answer($winner, $this->why($candidates, $separator, $context), $assumedPlace);
    }

    /** @param  list<Candidate>  $candidates */
    private function continuation(User $user, array $candidates): ?Candidate
    {
        $session = $user->runningSession()->getResults();

        if (! $session instanceof ExecutionSession || $session->current_step_id === null) {
            return null;
        }

        foreach ($candidates as $candidate) {
            if ($candidate->step->id === $session->current_step_id) {
                return $candidate;
            }
        }

        return null;
    }

    private function rank(Candidate $a, Candidate $b, ResolutionContext $context): int
    {
        foreach ($this->chain as $comparator) {
            $verdict = $comparator->compare($a, $b, $context);

            if ($verdict !== 0) {
                return $verdict;
            }
        }

        return 0;
    }

    /**
     * @param  list<Candidate>  $ranked
     * @return list<string>
     */
    private function why(array $ranked, int $separator, ResolutionContext $context): array
    {
        $winner = $ranked[0];

        $why = $separator >= 0 ? [$this->chain[$separator]->decides($winner, $context)] : [];

        foreach (array_slice($this->chain, $separator + 1) as $comparator) {
            $why[] = $comparator->qualifies($winner, $context);
        }

        $why = array_values(array_filter($why));

        return $why === [] ? [(string) $this->chain[count($this->chain) - 1]->decides($winner, $context)] : $why;
    }

    /** Rungs above the separator stay silent in the why; the separator and everything below it speak. */
    private function speaks(Rung $rung, int $separator): bool
    {
        $position = array_search($rung, $this->chain, true);

        return $position !== false && $separator <= $position;
    }

    /**
     * The highest rung that put anything below the winner, since a sibling step ties all the way down.
     *
     * @param  list<Candidate>  $ranked
     */
    private function separator(array $ranked, ResolutionContext $context): int
    {
        $winner = $ranked[0];
        $others = array_slice($ranked, 1);

        foreach ($this->chain as $index => $comparator) {
            foreach ($others as $other) {
                if ($comparator->compare($winner, $other, $context) !== 0) {
                    return $index;
                }
            }
        }

        return -1;
    }

    /** @param  list<string>  $why */
    private function answer(Candidate $candidate, array $why, ?Place $assumedPlace): NextActionData
    {
        return new NextActionData(
            StepData::from($candidate->step),
            IntentionData::from($candidate->intention),
            $why,
            $assumedPlace,
        );
    }
}
