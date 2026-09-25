<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Models\User;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsObject;

/** Withdrawing sets the column back to null rather than false, so "never asked" and "said no" stay distinguishable in the data. */
final class UpdateAiConsent
{
    use AsObject;

    public function handle(User $user, bool $consented): void
    {
        $user->ai_consented_at = $consented ? Carbon::now() : null;
        $user->save();
    }
}
