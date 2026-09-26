<?php

declare(strict_types=1);

namespace App\Actions\Commitments;

use App\Enums\CommitmentProvenance;
use App\Models\Commitment;
use App\Models\Intention;
use Lorisleiva\Actions\Concerns\AsObject;

/** Confirm-only: the person is looking at the intention they are promoting, so `confirmed_at` is immediate. */
final class PromoteIntentionToCommitment
{
    use AsObject;

    public function handle(Intention $intention): Commitment
    {
        return CreateCommitment::run($intention->user, $intention->title, CommitmentProvenance::UserTask);
    }
}
