<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\ExecutionSession;
use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesOwnedSession
{
    /** Someone else's session is not found rather than forbidden: its existence is not theirs to learn. */
    protected function ownedSession(Request $request, ExecutionSession $session): ExecutionSession
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);
        abort_unless($session->user_id === $user->id, 404);

        return $session;
    }
}
