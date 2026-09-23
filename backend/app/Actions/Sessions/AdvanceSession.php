<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\IntentionStatus;
use App\Enums\SessionOutcome;
use App\Models\ExecutionSession;
use App\Models\Step;
use App\Support\Execution\SessionState;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/** Points the session at the next step it can offer, and ends it when there is none. */
final class AdvanceSession
{
    use AsObject;

    public function handle(ExecutionSession $session, ?string $exceptStepId = null): ExecutionSession
    {
        SessionState::assertOpen($session);

        $pending = $session->intention->remainingSteps()->orderBy('position')->get();
        $offerable = $pending->reject(fn (Step $step): bool => $step->id === $exceptStepId);
        $next = $this->next($offerable, $session);

        if (! $next instanceof Step) {
            return $this->end($session, $pending->isEmpty() ? SessionOutcome::Completed : SessionOutcome::Continued);
        }

        $session->update(['current_step_id' => $next->id]);

        return $session;
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
        }

        return $session;
    }
}
