<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DismissReminderController extends Controller
{
    public function __invoke(Request $request, string $notification): RedirectResponse
    {
        $user = $this->user($request);

        $user->unreadNotifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return back();
    }
}
