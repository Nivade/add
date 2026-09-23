<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Calendar\DisconnectCalendarFeed;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class DisconnectCalendarController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        DisconnectCalendarFeed::run($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Calendar disconnected.')]);

        return to_route('calendar.edit');
    }
}
