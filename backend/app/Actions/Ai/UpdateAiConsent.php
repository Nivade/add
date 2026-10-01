<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Actions\Captures\SortCapture;
use App\Models\Capture;
use App\Models\User;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsObject;

/** Withdrawing sets the column back to null rather than false, so "never asked" and "said no" stay distinguishable in the data. */
final class UpdateAiConsent
{
    use AsObject;

    public function handle(User $user, bool $consented): void
    {
        $wasConsented = $user->hasConsentedToAi();

        $user->ai_consented_at = $consented ? Carbon::now() : null;
        $user->save();

        if ($consented && ! $wasConsented) {
            $this->sortWhatFailed($user);
        }
    }

    /** Only what failed: a capture still in its first job must not get a second one. */
    private function sortWhatFailed(User $user): void
    {
        Capture::query()
            ->where('user_id', $user->id)
            ->whereNull('processed_at')
            ->whereNotNull('failed_at')
            ->each(function (Capture $capture): void {
                $capture->update(['failed_at' => null]);
                SortCapture::dispatch($capture);
            });
    }
}
