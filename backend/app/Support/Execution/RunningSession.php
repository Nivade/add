<?php

declare(strict_types=1);

namespace App\Support\Execution;

use App\Models\ExecutionSession;
use App\Models\User;

/** One answer to "is there a session open", so home, focus and the resolver cannot disagree. */
final class RunningSession
{
    public static function forUser(User $user): ?ExecutionSession
    {
        return ExecutionSession::query()
            ->where('user_id', $user->id)
            ->running()
            ->latest('started_at')
            ->latest('id')
            ->first();
    }

    /** Held while a session is opened, so two taps on Start cannot both find nothing running. */
    public static function lockKey(User $user): string
    {
        return 'execution-session:'.$user->id;
    }
}
