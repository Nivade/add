<?php

declare(strict_types=1);

namespace App\Support\NextAction;

use App\Contracts\NextActionResolver;
use App\Data\IntentionData;
use App\Data\NextActionData;
use App\Data\StepData;
use App\Models\ExecutionSession;
use App\Models\User;
use App\Support\NextAction\Comparators\DeadlineWithinReach;
use App\Support\NextAction\Comparators\EarliestPosition;
use App\Support\NextAction\Comparators\HasDeadline;
use App\Support\NextAction\Comparators\NotRecentlySkipped;
use App\Support\NextAction\Comparators\OldestIntention;
use App\Support\NextAction\Comparators\StartableNow;

/** No score: a number six inputs went into cannot be explained, and the `why` has to be. */
final class ChainedNextActionResolver implements NextActionResolver
{
    /** @var list<StepComparator> */
    private array $chain;

    public function __construct()
    {
        $this->chain = [
            new DeadlineWithinReach,
            new HasDeadline,
            new NotRecentlySkipped,
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
            return $this->answer($continued, ['You are part-way through this one.']);
        }

        usort($candidates, fn (Candidate $a, Candidate $b): int => $this->rank($a, $b, $context));

        return $this->answer($candidates[0], $this->why($candidates, $context));
    }

    /** @param  list<Candidate>  $candidates */
    private function continuation(User $user, array $candidates): ?Candidate
    {
        $session = ExecutionSession::query()
            ->where('user_id', $user->id)
            ->running()
            ->whereNotNull('current_step_id')
            ->latest('started_at')
            ->first();

        if ($session === null) {
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
    private function why(array $ranked, ResolutionContext $context): array
    {
        $winner = $ranked[0];
        $separator = $this->separator($ranked, $context);

        $why = $separator >= 0 ? [$this->chain[$separator]->decides($winner, $context)] : [];

        foreach (array_slice($this->chain, $separator + 1) as $comparator) {
            $why[] = $comparator->qualifies($winner, $context);
        }

        $why = array_values(array_filter($why));

        return $why === [] ? [(string) $this->chain[count($this->chain) - 1]->decides($winner, $context)] : $why;
    }

    /**
     * The highest rung that put anything below the winner. A sibling of the same
     * intention ties all the way down, so the runner-up alone would hide the reason.
     *
     * @param  list<Candidate>  $ranked
     */
    private function separator(array $ranked, ResolutionContext $context): int
    {
        $winner = $ranked[0];
        $separator = -1;

        foreach (array_slice($ranked, 1) as $other) {
            foreach ($this->chain as $index => $comparator) {
                if ($comparator->compare($winner, $other, $context) !== 0) {
                    $separator = $separator === -1 ? $index : min($separator, $index);

                    break;
                }
            }
        }

        return $separator;
    }

    /** @param  list<string>  $why */
    private function answer(Candidate $candidate, array $why): NextActionData
    {
        return new NextActionData(
            StepData::from($candidate->step),
            IntentionData::from($candidate->intention),
            $why,
        );
    }
}
