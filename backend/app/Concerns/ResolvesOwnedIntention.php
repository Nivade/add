<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Intention;
use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesOwnedIntention
{
    /** Someone else's intention is not found rather than forbidden: its existence is not theirs to learn. */
    protected function ownedIntention(Request $request, Intention $intention): Intention
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);
        abort_unless($intention->user_id === $user->id, 404);

        return $intention;
    }
}
