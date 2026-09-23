<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class DismissReminderController extends Controller
{
    public function __invoke(Request $request, string $notification): Response
    {
        $this->user($request)->unreadNotifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return response()->noContent();
    }
}
