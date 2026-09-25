<?php

declare(strict_types=1);

namespace App\Actions\Commitments;

use App\Enums\CommitmentProvenance;
use App\Models\Commitment;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsObject;

/** `user_task` and `user_stated` confirm themselves by existing; `system_inferred` waits for a producer. */
final class CreateCommitment
{
    use AsObject;

    public function handle(User $user, string $description, CommitmentProvenance $provenance): Commitment
    {
        return Commitment::query()->create([
            'user_id' => $user->id,
            'description' => trim($description),
            'provenance' => $provenance,
            'confirmed_at' => $provenance->isInferred() ? null : now(),
        ]);
    }
}
