<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Models\Commitment;
use App\Models\Intention;
use App\Models\User;
use App\Models\WaitingFor;
use Lorisleiva\Actions\Concerns\AsObject;

/** One meaning for "everything else" on every screen that states it. */
final class CountOpenThings
{
    use AsObject;

    public function handle(User $user): int
    {
        return Intention::query()->where('user_id', $user->id)->open()->count()
            + WaitingFor::query()->where('user_id', $user->id)->open()->count()
            + Commitment::query()->where('user_id', $user->id)->open()->standalone()->count();
    }
}
