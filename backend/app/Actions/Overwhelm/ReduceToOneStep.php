<?php

declare(strict_types=1);

namespace App\Actions\Overwhelm;

use App\Data\IntentionData;
use App\Data\NextActionData;
use App\Data\OverwhelmedData;
use App\Data\StepData;
use App\Models\User;
use App\Support\NextAction\Candidate;
use App\Support\NextAction\CandidatePool;
use App\Support\NextAction\Comparators\NotRecentlySkipped;
use App\Support\NextAction\ResolutionContext;
use Lorisleiva\Actions\Concerns\AsObject;

/** A different question from "what is most useful": the shortest thing that can be finished. */
final class ReduceToOneStep
{
    use AsObject;

    public function handle(User $user, ResolutionContext $context): OverwhelmedData
    {
        $candidates = CandidatePool::forUser($user);

        if ($candidates === []) {
            return new OverwhelmedData(null, 0);
        }

        $coolOff = new NotRecentlySkipped;

        usort($candidates, fn (Candidate $a, Candidate $b): int => $coolOff->compare($a, $b, $context)
            ?: $this->size($a) <=> $this->size($b));

        $smallest = $candidates[0];

        return new OverwhelmedData(
            new NextActionData(
                StepData::from($smallest->step),
                IntentionData::from($smallest->intention),
                $this->why($smallest, count($candidates) - 1),
            ),
            count($candidates) - 1,
        );
    }

    /**
     * An unestimated step loses a tie to a timed one of the same assumed cost.
     *
     * @return array{int, bool, int, string}
     */
    private function size(Candidate $candidate): array
    {
        return [
            $candidate->cost(),
            $candidate->estimatedSeconds() === null,
            $candidate->step->position,
            $candidate->step->id,
        ];
    }

    /** @return list<string> */
    private function why(Candidate $candidate, int $restCount): array
    {
        $estimate = $candidate->estimateInWords();

        return [
            $estimate === null ? 'Nobody has estimated this one.' : "This takes about {$estimate}.",
            $restCount === 0 ? 'It is the only thing left.' : 'Nothing else left is shorter.',
        ];
    }
}
