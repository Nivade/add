<?php

declare(strict_types=1);

namespace App\Actions\Metrics;

use App\Data\Metrics\SessionOutcomesData;
use App\Enums\ExecutionEventType;
use App\Models\ExecutionEvent;
use App\Models\ExecutionSession;
use App\Support\Metrics\MetricsWindow;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsObject;

/** Progress is at least one step done; a distraction counts as recovered once a step is done after it. */
final class MeasureSessions
{
    use AsObject;

    public function handle(MetricsWindow $window): SessionOutcomesData
    {
        $ended = $window->within($window->scope(ExecutionSession::query()), 'ended_at');

        $distractions = $window->within(ExecutionEvent::query(), 'created_at')
            ->where('type', ExecutionEventType::Distracted)
            ->whereHas('session', fn (Builder $query): Builder => $window->scope($query))
            ->get(['id', 'execution_session_id', 'created_at']);

        $completions = ExecutionEvent::query()
            ->where('type', ExecutionEventType::StepCompleted)
            ->whereIn('execution_session_id', $distractions->pluck('execution_session_id')->unique()->values())
            ->get(['id', 'execution_session_id', 'created_at'])
            ->groupBy('execution_session_id');

        return new SessionOutcomesData(
            ended: (clone $ended)->count(),
            withProgress: $ended->where('steps_completed', '>=', 1)->count(),
            distractions: $distractions->count(),
            backAfterDistraction: $distractions->filter(
                fn (ExecutionEvent $distraction): bool => ($completions->get($distraction->execution_session_id) ?? collect())
                    ->contains(fn (ExecutionEvent $completion): bool => $completion->happenedAfter($distraction))
            )->count(),
        );
    }
}
