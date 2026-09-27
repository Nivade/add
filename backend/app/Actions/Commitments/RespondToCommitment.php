<?php

declare(strict_types=1);

namespace App\Actions\Commitments;

use App\Enums\CommitmentResponse;
use App\Enums\CommitmentStatus;
use App\Exceptions\InvalidCommitmentResponse;
use App\Models\Commitment;
use Lorisleiva\Actions\Concerns\AsObject;

/** Keeping an inferred commitment confirms it too: the person just said it was theirs. */
final class RespondToCommitment
{
    use AsObject;

    public function handle(Commitment $commitment, CommitmentResponse $response): Commitment
    {
        if ($commitment->status !== CommitmentStatus::Open) {
            throw new InvalidCommitmentResponse("Commitment {$commitment->id} is no longer open.");
        }

        if ($response === CommitmentResponse::Confirm && ! $commitment->awaitsConfirmation()) {
            throw new InvalidCommitmentResponse("Commitment {$commitment->id} has nothing to confirm.");
        }

        $commitment->update(match ($response) {
            CommitmentResponse::Confirm => ['confirmed_at' => now()],
            CommitmentResponse::Keep => ['status' => CommitmentStatus::Kept, 'confirmed_at' => $commitment->confirmed_at ?? now()],
            CommitmentResponse::Release => ['status' => CommitmentStatus::Released],
        });

        return $commitment;
    }
}
