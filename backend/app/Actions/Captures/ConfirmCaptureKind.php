<?php

declare(strict_types=1);

namespace App\Actions\Captures;

use App\Actions\Commitments\RespondToCommitment;
use App\Enums\CaptureKind;
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
            $commitment = $capture->kind === CaptureKind::Promise ? Commitment::query()->find($capture->routed_id) : null;

            if ($commitment?->awaiting_confirmation === true) {
                RespondToCommitment::run($commitment, CommitmentResponse::Confirm);
            }

            $capture->update(['kind_confirmed_at' => now()]);

            return $capture;
        });
    }
}
