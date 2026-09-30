<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Actions\Steps\SplitStep;
use App\Actions\Whereabouts\ReportNotHere;
use App\Enums\ExecutionEventType;
use App\Enums\Place;
use App\Enums\StuckReason;
use App\Enums\StuckResolution;
use App\Models\ExecutionSession;
use App\Models\Step;
use App\Support\NextAction\Candidate;
use App\Support\NextAction\CandidatePool;
use App\Support\NextAction\ResolutionContext;
use App\Support\NextAction\SmallestFirst;
use Lorisleiva\Actions\Concerns\AsObject;

/** Every answer leaves the person with something to start or a clean stop. */
final class ReportStuck
{
    use AsObject;

    public function handle(ExecutionSession $session, string $stepId, StuckReason $reason, ?string $note = null): ExecutionSession
    {
        return $session->transition(function () use ($session, $stepId, $reason, $note): ExecutionSession {
            $step = $session->currentStepOrFail($stepId);

            RecordExecutionEvent::run($session, ExecutionEventType::Stuck, $step->id, [
                'reason' => $reason->value,
                'note' => $note,
            ]);

            return match ($reason->resolution()) {
                StuckResolution::Split => $this->makeItSmaller($session, $step),
                StuckResolution::NextStep => AdvanceSession::run($session, $step->is(...)),
                StuckResolution::Stop => StopSession::run($session),
                StuckResolution::StayPut => $session,
                StuckResolution::Elsewhere => $this->elsewhere($session, $step),
            };
        });
    }

    /** The move is deterministic and immediate; the split lands whenever the model answers. */
    private function makeItSmaller(ExecutionSession $session, Step $step): ExecutionSession
    {
        SplitStep::dispatch($step)->afterCommit();

        $smallest = $this->shortestSibling($session, $step);

        if (! $smallest instanceof Candidate) {
            // Nothing shorter to offer, so advance rather than leave them on the step they just refused.
            return AdvanceSession::run($session, $step->is(...));
        }

        $session->update(['current_step_id' => $smallest->step->id]);

        return $session;
    }

    /** Not a skip: being in the wrong place is not avoidance, so skip_count stays where it is. */
    private function elsewhere(ExecutionSession $session, Step $step): ExecutionSession
    {
        $place = $step->place;

        if (! $place instanceof Place) {
            return AdvanceSession::run($session, $step->is(...));
        }

        ReportNotHere::run($session->user, $place);

        return AdvanceSession::run($session, fn (Step $pending): bool => $pending->place === $place);
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
