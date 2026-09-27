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
        $existing = Commitment::query()->open()->where('intention_id', $intention->id)->first();

        return $existing ?? CreateCommitment::run($intention->user, $intention->title, CommitmentProvenance::UserTask, $intention);
    }
}
