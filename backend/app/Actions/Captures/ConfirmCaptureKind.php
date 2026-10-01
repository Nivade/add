<?php

declare(strict_types=1);

namespace App\Actions\Captures;

use App\Actions\Commitments\RespondToCommitment;
use App\Enums\CommitmentResponse;
use App\Models\Capture;
use App\Models\Commitment;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

/** Saying the sort was right about a promise is saying the promise is theirs. */
final class ConfirmCaptureKind
{
    use AsObject;

    public function handle(Capture $capture): Capture
    {
        return DB::transaction(function () use ($capture): Capture {
            $routed = $capture->routed();

            if ($routed instanceof Commitment && $routed->awaiting_confirmation) {
                RespondToCommitment::run($routed, CommitmentResponse::Confirm);
            }

            $capture->update(['kind_confirmed_at' => now()]);

            return $capture;
        });
    }
}
