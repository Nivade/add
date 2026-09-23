<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Calendar\ConnectCalendarFeed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CalendarFeedUpdateRequest;
use App\Support\Calendar\Exceptions\CalendarFeedUnreadable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

final class ConnectCalendarController extends Controller
{
    public function __invoke(CalendarFeedUpdateRequest $request): RedirectResponse
    {
        try {
            ConnectCalendarFeed::run($request->user(), $request->string('url')->toString());
        } catch (CalendarFeedUnreadable) {
            throw ValidationException::withMessages(['url' => __('That address did not answer with a calendar.')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Calendar connected.')]);

        return to_route('calendar.edit');
    }
}
