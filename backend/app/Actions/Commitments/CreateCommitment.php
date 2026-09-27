<?php

declare(strict_types=1);

namespace App\Actions\Commitments;

use App\Enums\CommitmentProvenance;
use App\Enums\CommitmentStatus;
use App\Models\Commitment;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsObject;

/** One commitment per intention or step: committing again reopens it rather than adding a second. */
final class CreateCommitment
{
    use AsObject;

    public function handle(User $user, string $description, CommitmentProvenance $provenance, ?Intention $intention = null, ?Step $step = null): Commitment
    {
        $existing = match (true) {
            $intention instanceof Intention => Commitment::query()->forIntention($intention->id)->first(),
            $step instanceof Step => Commitment::query()->forStep($step->id)->first(),
            default => null,
        };

        if ($existing instanceof Commitment) {
            $existing->update(['status' => CommitmentStatus::Open]);

            return $existing;
        }

        return Commitment::query()->create([
            'user_id' => $user->id,
            'intention_id' => $intention?->id,
            'step_id' => $step?->id,
            'description' => trim($description),
            'provenance' => $provenance,
            'confirmed_at' => $provenance->isInferred() ? null : now(),
            'status' => CommitmentStatus::Open,
        ]);
    }
}
