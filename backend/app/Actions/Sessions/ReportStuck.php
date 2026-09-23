<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Actions\Steps\SplitStep;
use App\Enums\ExecutionEventType;
use App\Enums\StuckReason;
use App\Enums\StuckResolution;
use App\Models\ExecutionSession;
use App\Models\Step;
use App\Support\Execution\SessionState;
use App\Support\NextAction\Candidate;
use App\Support\NextAction\CandidatePool;
use App\Support\NextAction\ResolutionContext;
use App\Support\NextAction\SmallestFirst;
use Lorisleiva\Actions\Concerns\AsObject;

/** Every answer leaves the person with something to start or a clean stop. */
final class ReportStuck
{
    use AsObject;

    public function handle(ExecutionSession $session, StuckReason $reason, ?string $note = null): ExecutionSession
    {
        $step = SessionState::currentStep($session);

        RecordExecutionEvent::run($session, ExecutionEventType::Stuck, $step->id, [
            'reason' => $reason->value,
            'note' => $note,
        ]);

        return match ($reason->resolution()) {
            StuckResolution::Split => $this->makeItSmaller($session, $step),
            StuckResolution::NextStep => AdvanceSession::run($session, $step->id),
            StuckResolution::Stop => StopSession::run($session),
            StuckResolution::StayPut => $session,
        };
    }

    /** The move is deterministic and immediate; the split lands whenever the model answers. */
    private function makeItSmaller(ExecutionSession $session, Step $step): ExecutionSession
    {
        SplitStep::dispatch($step);

        $smallest = $this->shortestSibling($session, $step);

        if (! $smallest instanceof Candidate) {
            // Nothing shorter to offer, so advance rather than leave them on the step they just refused.
            return AdvanceSession::run($session, $step->id);
        }

        $session->update(['current_step_id' => $smallest->step->id]);

        return $session;
    }

    private function shortestSibling(ExecutionSession $session, Step $step): ?Candidate
    {
        $siblings = array_values(array_filter(
            CandidatePool::forIntention($session->intention),
            fn (Candidate $candidate): bool => $candidate->step->id !== $step->id,
        ));

        return SmallestFirst::sort($siblings, ResolutionContext::forUser($session->user))[0] ?? null;
    }
}
