<?php

declare(strict_types=1);

namespace App\Actions\Commitments;

use App\Enums\CommitmentProvenance;
use App\Enums\IntentionStatus;
use App\Exceptions\InvalidIntentionTransition;
use App\Models\Commitment;
use App\Models\Intention;
use Lorisleiva\Actions\Concerns\AsObject;

/** Confirm-only: the person is looking at the intention they are promoting. */
final class PromoteIntentionToCommitment
{
    use AsObject;

    public function handle(Intention $intention): Commitment
    {
        if ($intention->status === IntentionStatus::Done) {
            throw new InvalidIntentionTransition("Intention {$intention->id} is already done.");
        }

        return CreateCommitment::run($intention->user, $intention->title, CommitmentProvenance::UserTask, $intention);
    }
}
