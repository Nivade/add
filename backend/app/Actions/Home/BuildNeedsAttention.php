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

    /** @return array{items: list<NeedsAttentionData>, outstanding: int} */
    public function handle(User $user, CarbonImmutable $now): array
    {
        $openWaitingFors = WaitingFor::query()->where('user_id', $user->id)->open()->orderBy('updated_at')->get();
        $openCommitments = Commitment::query()->where('user_id', $user->id)->open()->mostPressingFirst()->get();

        $staleWaitingFor = $this->staleWaitingFor($openWaitingFors, $now);
        $commitment = $openCommitments->first();

        return [
            'items' => [
                ...$this->awaitingClarification($user),
                ...($staleWaitingFor instanceof WaitingFor ? [NeedsAttentionData::forWaitingFor($staleWaitingFor)] : []),
                ...($commitment instanceof Commitment ? [NeedsAttentionData::forCommitment($commitment)] : []),
            ],
            'outstanding' => $openWaitingFors->count() + $openCommitments->count(),
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

    /**
     * `updated_at` is the last time it was touched, by creation or by a response — the one clock this needs.
     *
     * @param  Collection<int, WaitingFor>  $openWaitingFors
     */
    private function staleWaitingFor(Collection $openWaitingFors, CarbonImmutable $now): ?WaitingFor
    {
        $threshold = $now->subDays(self::WAITING_FOR_STALE_AFTER_DAYS);

        return $openWaitingFors->first(fn (WaitingFor $waitingFor): bool => $waitingFor->updated_at !== null && $waitingFor->updated_at->lte($threshold));
    }
}
