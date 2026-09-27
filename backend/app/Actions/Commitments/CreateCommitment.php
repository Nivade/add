<?php

declare(strict_types=1);

namespace App\Actions\Commitments;

use App\Enums\CommitmentProvenance;
use App\Enums\CommitmentStatus;
use App\Models\Commitment;
use App\Models\Intention;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsObject;

/** `user_task` and `user_stated` confirm themselves by existing; `system_inferred` waits for a producer. */
final class CreateCommitment
{
    use AsObject;

    public function handle(User $user, string $description, CommitmentProvenance $provenance, ?Intention $intention = null): Commitment
    {
        return Commitment::query()->create([
            'user_id' => $user->id,
            'intention_id' => $intention?->id,
            'description' => trim($description),
            'provenance' => $provenance,
            'confirmed_at' => $provenance->isInferred() ? null : now(),
            'status' => CommitmentStatus::Open,
        ]);
    }
}
