<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Models\Commitment;
use App\Models\Intention;
use App\Models\User;
use App\Models\WaitingFor;
use Lorisleiva\Actions\Concerns\AsObject;

/** One meaning for "everything else": a commitment tied to an intention or step is already counted through it. */
final class CountOpenThings
{
    use AsObject;

    public function handle(User $user): int
    {
        return Intention::query()->where('user_id', $user->id)->open()->count()
            + WaitingFor::query()->where('user_id', $user->id)->open()->count()
            + Commitment::query()->where('user_id', $user->id)->open()->whereNull('intention_id')->whereNull('step_id')->count();
    }
}
