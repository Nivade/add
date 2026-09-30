<?php

declare(strict_types=1);

namespace App\Actions\Metrics;

use App\Data\Metrics\RecoveryOutcomesData;
use App\Enums\ExecutionEventType;
use App\Enums\IntentionStatus;
use App\Enums\SessionOutcome;
use App\Enums\StepStatus;
use App\Models\ExecutionEvent;
use App\Models\ExecutionSession;
use App\Models\Step;
use App\Support\Metrics\MetricsWindow;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/** There is no overdue, so recovery is a stopped stretch started again and a skipped step done after all. */
final class MeasureRecovery
{
    use AsObject;

    public const int RECOVERY_DAYS = 7;

    public function handle(MetricsWindow $window): RecoveryOutcomesData
    {
        $landed = $window->within($window->scope(ExecutionSession::query()), 'ended_at')
            ->where('outcome', '!=', SessionOutcome::Completed)
            ->with('intention:id,status')
            ->get(['id', 'intention_id', 'ended_at']);

        $restarts = $this->startsOn($landed);

        $skipped = $window->within(Step::query(), 'last_skipped_at')
            ->where('skip_count', '>', 0)
            ->whereHas('intention', fn (Builder $query): Builder => $window->scope($query));

        return new RecoveryOutcomesData(
            landedUnfinished: $landed->count(),
            pickedBackUp: $landed->filter(fn (ExecutionSession $session): bool => $this->pickedBackUp($session, $restarts))->count(),
            skippedSteps: (clone $skipped)->count(),
            skippedThenDone: $skipped->where('status', StepStatus::Done)->count(),
        );
    }

    /** @param  Collection<array-key, array<int, CarbonImmutable>>  $restarts */
    private function pickedBackUp(ExecutionSession $session, Collection $restarts): bool
    {
        if ($session->intention->status === IntentionStatus::Done) {
            return true;
        }

        $endedAt = $session->ended_at;
        $until = $endedAt?->addDays(self::RECOVERY_DAYS);

        return collect($restarts->get($session->intention_id, []))
            ->contains(fn (CarbonImmutable $at): bool => $at->greaterThan($endedAt) && $at->lessThanOrEqualTo($until));
    }

    /**
     * @param  EloquentCollection<int, ExecutionSession>  $landed
     * @return Collection<array-key, array<int, CarbonImmutable>>
     */
    private function startsOn(EloquentCollection $landed): Collection
    {
        return ExecutionEvent::query()
            ->join('execution_sessions', 'execution_sessions.id', '=', 'execution_events.execution_session_id')
            ->where('execution_events.type', ExecutionEventType::Started)
            ->whereIn('execution_sessions.intention_id', $landed->pluck('intention_id')->unique())
            ->get(['execution_sessions.intention_id', 'execution_events.created_at'])
            ->groupBy('intention_id')
            ->map(fn (Collection $starts): array => $starts->map(fn (ExecutionEvent $start): CarbonImmutable => $start->created_at)->values()->all());
    }
}
