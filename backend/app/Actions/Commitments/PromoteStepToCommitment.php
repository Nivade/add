<?php

declare(strict_types=1);

namespace App\Actions\Commitments;

use App\Enums\CommitmentProvenance;
use App\Models\Commitment;
use App\Models\ExecutionSession;
use Lorisleiva\Actions\Concerns\AsObject;

/** A step is reached through the session offering it, so the one promoted is the one on screen. */
final class PromoteStepToCommitment
{
    use AsObject;

    public function handle(ExecutionSession $session, string $stepId): Commitment
    {
        $step = $session->currentStepOrFail($stepId);

        return CreateCommitment::run($session->user, $step->title, CommitmentProvenance::UserTask, step: $step);
    }
}
