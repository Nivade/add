<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\CommitmentStatus;
use App\Enums\ExecutionEventType;
use App\Enums\IntentionStatus;
use App\Enums\SessionOutcome;
use App\Enums\StuckReason;
use App\Enums\StuckResolution;
use App\Models\Commitment;
use App\Models\ExecutionSession;
use App\Models\Step;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/** Points the session at the next step it can offer, and ends it when there is none. */
final class AdvanceSession
{
    use AsObject;

    /** @param  (Closure(Step): bool)|null  $passOver  steps not to offer next, which still count as left */
    public function handle(ExecutionSession $session, ?Closure $passOver = null): ExecutionSession
    {
        return $session->transition(function () use ($session, $passOver): ExecutionSession {
            $pending = $session->intention->remainingSteps()->orderBy('position')->get();
            $tooBig = $this->reportedTooBig($session);
            $offerable = $pending->reject(
                fn (Step $step): bool => in_array($step->id, $tooBig, true) || ($passOver instanceof Closure && $passOver($step)),
            );
            $next = $this->next($offerable, $session);

            if (! $next instanceof Step) {
                return $this->end($session, $pending->isEmpty() ? SessionOutcome::Completed : SessionOutcome::Continued);
            }

            $session->update(['current_step_id' => $next->id]);

            return $session;
        });
    }

    /**
     * A step they said was too big stays theirs, but is not handed back in the same sitting.
     *
     * @return list<string>
     */
    private function reportedTooBig(ExecutionSession $session): array
    {
        $splitReasons = array_values(array_map(
            fn (StuckReason $reason): string => $reason->value,
            array_filter(StuckReason::cases(), fn (StuckReason $reason): bool => $reason->resolution() === StuckResolution::Split),
        ));

        /** @var list<string> */
        return $session->events()
            ->where('type', ExecutionEventType::Stuck)
            ->whereIn('payload->reason', $splitReasons)
            ->whereNotNull('step_id')
            ->pluck('step_id')
            ->all();
    }

    /** @param  Collection<int, Step>  $offerable */
    private function next(Collection $offerable, ExecutionSession $session): ?Step
    {
        $current = $session->current_step_id === null ? null : $session->currentStep()->getResults();
        $position = $current->position ?? 0;

        // Wrapping to the front keeps a skip early in the list from stranding the tail.
        return $offerable->first(fn (Step $step): bool => $step->position > $position) ?? $offerable->first();
    }

    private function end(ExecutionSession $session, SessionOutcome $outcome): ExecutionSession
    {
        LandSession::run($session, $outcome);

        if ($outcome === SessionOutcome::Completed) {
            $session->intention->update([
                'status' => IntentionStatus::Done,
                'completed_at' => now(),
            ]);

            Commitment::query()->open()->forIntention($session->intention_id)->update(['status' => CommitmentStatus::Kept]);
        }

        return $session;
    }
}
