<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DismissReminderController extends Controller
{
    public function __invoke(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $user->unreadNotifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return back();
    }
}
