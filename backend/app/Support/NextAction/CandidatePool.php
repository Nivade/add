<?php

declare(strict_types=1);

namespace App\Support\NextAction;

use App\Enums\IntentionStatus;
use App\Models\Step;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;

/** Everything the person could legitimately be pointed at, before anything ranks it. */
final class CandidatePool
{
    /** @return list<Candidate> */
    public static function forUser(User $user): array
    {
        $steps = Step::query()
            ->pending()
            ->whereHas('intention', fn (Builder $intention) => $intention
                ->where('user_id', $user->id)
                ->where('status', IntentionStatus::Active)
                ->whereNotNull('decomposed_at')
                ->where('needs_clarification', false))
            ->with('intention')
            ->get();

        $remaining = [];

        foreach ($steps as $step) {
            $remaining[$step->intention_id] = ($remaining[$step->intention_id] ?? 0) + Candidate::costOf($step);
        }

        return array_values($steps
            ->map(fn (Step $step): Candidate => new Candidate(
                $step,
                $step->intention,
                $remaining[$step->intention_id],
            ))
            ->all());
    }
}
