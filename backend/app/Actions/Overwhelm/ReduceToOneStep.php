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
use App\Support\NextAction\ResolutionContext;
use App\Support\NextAction\SmallestFirst;
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

        $candidates = SmallestFirst::sort($candidates, $context);
        $smallest = $candidates[0];

        return new OverwhelmedData(
            new NextActionData(
                StepData::from($smallest->step),
                IntentionData::from($smallest->intention),
                $this->why($smallest, count($candidates) - 1, $context),
            ),
            count($candidates) - 1,
        );
    }

    /** @return list<string> */
    private function why(Candidate $candidate, int $restCount, ResolutionContext $context): array
    {
        $estimate = $candidate->estimateInWords();

        $why = [
            $estimate === null ? 'Nobody has estimated this one.' : "This takes about {$estimate}.",
            $restCount === 0 ? 'It is the only thing left.' : 'Nothing else left is shorter.',
        ];

        $available = $context->availableInWords();

        if ($available !== null && $context->availableSeconds >= $candidate->cost()) {
            $why[] = "You have {$available} before you need to leave, so it fits.";
        }

        return $why;
    }
}
