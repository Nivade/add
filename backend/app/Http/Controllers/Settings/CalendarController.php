<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Calendar\ConnectCalendarFeed;
use App\Actions\Calendar\DisconnectCalendarFeed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CalendarFeedUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function edit(Request $request): Response
    {
        $url = $request->user()->calendar_feed_url;

        // The host is enough to recognise it; the rest of the URL is the credential and stays on the server.
        return Inertia::render('settings/calendar', [
            'connectedHost' => $url === null ? null : parse_url($url, PHP_URL_HOST),
        ]);
    }

    public function update(CalendarFeedUpdateRequest $request): RedirectResponse
    {
        ConnectCalendarFeed::run($request->user(), $request->string('url')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Calendar connected.')]);

        return to_route('calendar.edit');
    }

    public function destroy(Request $request): RedirectResponse
    {
        DisconnectCalendarFeed::run($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Calendar disconnected.')]);

        return to_route('calendar.edit');
    }
}
