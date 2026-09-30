<?php

declare(strict_types=1);

namespace App\Actions\Metrics;

use App\Data\Metrics\IntentionOutcomesData;
use App\Enums\ExecutionEventType;
use App\Enums\IntentionStatus;
use App\Models\ExecutionEvent;
use App\Models\Intention;
use App\Support\Metrics\MetricsWindow;
use App\Support\Metrics\Percentile;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/** The intentions created in the window, followed to wherever they are now. */
final class MeasureIntentions
{
    use AsObject;

    public function handle(MetricsWindow $window): IntentionOutcomesData
    {
        $cohort = $window->within($window->scope(Intention::query()), 'created_at')->get(['id', 'status', 'created_at']);
        $firstStarts = $this->firstStarts($cohort);

        $minutes = $cohort
            ->filter(fn (Intention $intention): bool => $firstStarts->has($intention->id))
            ->map(fn (Intention $intention): int => (int) $intention->created_at->diffInMinutes($firstStarts->get($intention->id)))
            ->values()
            ->all();

        return new IntentionOutcomesData(
            created: $cohort->count(),
            done: $cohort->where('status', IntentionStatus::Done)->count(),
            setAside: $cohort->where('status', IntentionStatus::SetAside)->count(),
            open: $cohort->whereIn('status', [IntentionStatus::Captured, IntentionStatus::Active])->count(),
            medianMinutesToStart: Percentile::of($minutes, 50),
            p75MinutesToStart: Percentile::of($minutes, 75),
            notStarted: $cohort->count() - count($minutes),
        );
    }

    /**
     * The aggregate comes back as the stored UTC string, so it is read as UTC.
     *
     * @param  EloquentCollection<int, Intention>  $cohort
     * @return Collection<array-key, CarbonImmutable>
     */
    private function firstStarts(EloquentCollection $cohort): Collection
    {
        return ExecutionEvent::query()
            ->join('execution_sessions', 'execution_sessions.id', '=', 'execution_events.execution_session_id')
            ->where('execution_events.type', ExecutionEventType::Started)
            ->whereIn('execution_sessions.intention_id', $cohort->modelKeys())
            ->groupBy('execution_sessions.intention_id')
            ->toBase()
            ->selectRaw('execution_sessions.intention_id as intention_id, min(execution_events.created_at) as first_start')
            ->pluck('first_start', 'intention_id')
            ->map(fn (mixed $at): CarbonImmutable => CarbonImmutable::parse((string) $at, 'UTC'));
    }
}
