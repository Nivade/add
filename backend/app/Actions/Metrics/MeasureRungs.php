<?php

declare(strict_types=1);

namespace App\Actions\Metrics;

use App\Data\Metrics\RungOutcomeData;
use App\Enums\ExecutionEventType;
use App\Models\ExecutionEvent;
use App\Support\Metrics\MetricsWindow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/** Whether a recommendation led anywhere: the step it started was done or skipped before the session moved on. */
final class MeasureRungs
{
    use AsObject;

    /** @return list<RungOutcomeData> */
    public function handle(MetricsWindow $window): array
    {
        $starts = $window->within(ExecutionEvent::query(), 'created_at')
            ->where('type', ExecutionEventType::Started)
            ->whereNotNull('payload')
            ->whereHas('session', fn (Builder $query): Builder => $window->scope($query))
            ->get(['id', 'execution_session_id', 'step_id', 'payload', 'created_at']);

        $endings = ExecutionEvent::query()
            ->whereIn('type', [ExecutionEventType::StepCompleted, ExecutionEventType::StepSkipped])
            ->whereIn('execution_session_id', $starts->pluck('execution_session_id')->unique()->values())
            ->get(['id', 'execution_session_id', 'step_id', 'type', 'created_at'])
            ->groupBy('execution_session_id')
            ->all();

        return array_values($starts
            ->groupBy(fn (ExecutionEvent $start): string => (string) ($start->payload['rung'] ?? ''))
            ->sortKeysUsing(fn (string $a, string $b): int => [$a === '', $a] <=> [$b === '', $b])
            ->map(fn (Collection $group, string $rung): RungOutcomeData => new RungOutcomeData(
                rung: $rung === '' ? null : $rung,
                starts: $group->count(),
                doneInSession: $this->endedAs($group, $endings, ExecutionEventType::StepCompleted),
                skippedInSession: $this->endedAs($group, $endings, ExecutionEventType::StepSkipped),
            ))
            ->all());
    }

    /**
     * @param  Collection<int, ExecutionEvent>  $starts
     * @param  array<array-key, EloquentCollection<int, ExecutionEvent>>  $endings
     */
    private function endedAs(Collection $starts, array $endings, ExecutionEventType $type): int
    {
        return $starts->filter(fn (ExecutionEvent $start): bool => ($endings[$start->execution_session_id] ?? new EloquentCollection)->contains(
            fn (ExecutionEvent $ending): bool => $ending->type === $type
                && $ending->step_id === $start->step_id
                && $ending->happenedAfter($start)
        ))->count();
    }
}
