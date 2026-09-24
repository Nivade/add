<?php

declare(strict_types=1);

namespace App\Support\NextAction;

use App\Enums\IntentionStatus;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** Everything the person could legitimately be pointed at, before anything ranks it. */
final class CandidatePool
{
    /** @return list<Candidate> */
    public static function forUser(User $user): array
    {
        return self::candidates(Step::query()
            ->pending()
            ->whereHas('intention', fn (Builder $intention) => $intention
                ->where('user_id', $user->id)
                ->where('status', IntentionStatus::Active)
                ->whereNotNull('decomposed_at')
                ->notAwaitingClarification())
            ->with('intention')
            ->get());
    }

    /** @return list<Candidate> */
    public static function forIntention(Intention $intention): array
    {
        return self::candidates($intention->remainingSteps()->get(), $intention);
    }

    /**
     * @param  Collection<int, Step>  $steps
     * @return list<Candidate>
     */
    private static function candidates(Collection $steps, ?Intention $intention = null): array
    {
        $remaining = [];

        foreach ($steps as $step) {
            $remaining[$step->intention_id] = ($remaining[$step->intention_id] ?? 0) + Candidate::costOf($step);
        }

        return array_values($steps
            ->map(fn (Step $step): Candidate => new Candidate(
                $step,
                $intention ?? $step->intention,
                $remaining[$step->intention_id],
            ))
            ->all());
    }
}
