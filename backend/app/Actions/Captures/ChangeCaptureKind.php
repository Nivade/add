<?php

declare(strict_types=1);

namespace App\Actions\Captures;

use App\Enums\CaptureKind;
use App\Enums\CommitmentStatus;
use App\Enums\WaitingForStatus;
use App\Exceptions\CaptureAlreadyActedOn;
use App\Models\Capture;
use App\Models\Commitment;
use App\Models\FutureReminder;
use App\Models\Intention;
use App\Models\WaitingFor;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

/** Re-sorting reads the stored parse, so no change of kind asks the model again. */
final class ChangeCaptureKind
{
    use AsObject;

    public function handle(Capture $capture, CaptureKind $kind): Capture
    {
        return DB::transaction(function () use ($capture, $kind): Capture {
            $routed = $capture->routed();

            if ($routed !== null && $this->actedOn($routed)) {
                throw new CaptureAlreadyActedOn("Capture {$capture->id} was already acted on as {$capture->kind?->value}.");
            }

            if ($routed instanceof Intention) {
                $routed->steps()->delete();
            }

            $routed?->delete();
            $capture->update(['intention_id' => null, 'routed_id' => null, 'kind' => null]);

            return SortCapture::run($capture, $kind);
        });
    }

    private function actedOn(Intention|WaitingFor|Commitment|FutureReminder $routed): bool
    {
        return match (true) {
            $routed instanceof Intention => $routed->sessions()->exists(),
            $routed instanceof WaitingFor => $routed->status !== WaitingForStatus::Waiting,
            $routed instanceof Commitment => $routed->status !== CommitmentStatus::Open,
            $routed instanceof FutureReminder => $routed->sent_at !== null,
        };
    }
}
