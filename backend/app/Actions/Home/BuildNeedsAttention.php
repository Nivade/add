<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Data\NeedsAttentionData;
use App\Models\Commitment;
use App\Models\Intention;
use App\Models\User;
use App\Models\WaitingFor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/** Each source offers at most its most pressing row, so the band never turns into a list. */
final class BuildNeedsAttention
{
    use AsObject;

    /** Enough to act on, few enough that the band is not a list to work through. */
    private const int CLARIFICATION_LIMIT = 3;

    /** How long a waiting-for goes untouched before it is worth a nudge. */
    private const int WAITING_FOR_STALE_AFTER_DAYS = 4;

    /**
     * @param  Collection<int, Commitment>  $standaloneCommitments  open, most pressing first
     * @return list<NeedsAttentionData>
     */
    public function handle(User $user, CarbonImmutable $now, Collection $standaloneCommitments): array
    {
        $staleWaitingFor = WaitingFor::query()->where('user_id', $user->id)->open()
            ->whereRaw('coalesce(last_answered_at, created_at) <= ?', [$now->subDays(self::WAITING_FOR_STALE_AFTER_DAYS)])
            ->orderByRaw('coalesce(last_answered_at, created_at)')
            ->first();
        $commitment = $standaloneCommitments->first();

        return [
            ...$this->awaitingClarification($user),
            ...($staleWaitingFor instanceof WaitingFor ? [NeedsAttentionData::forWaitingFor($staleWaitingFor)] : []),
            ...($commitment instanceof Commitment ? [NeedsAttentionData::forCommitment($commitment)] : []),
        ];
    }

    /** @return list<NeedsAttentionData> */
    private function awaitingClarification(User $user): array
    {
        return array_values(Intention::query()
            ->where('user_id', $user->id)
            ->open()
            ->awaitingClarification()
            ->oldest()
            ->limit(self::CLARIFICATION_LIMIT)
            ->get()
            ->map(fn (Intention $intention): NeedsAttentionData => NeedsAttentionData::forIntention($intention))
            ->all());
    }
}
