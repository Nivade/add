<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\FutureReminders\CreateRelativeFutureReminder;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRelativeFutureReminderRequest;
use App\Models\CalendarEvent;
use Illuminate\Http\RedirectResponse;

final class StoreRelativeFutureReminderController extends Controller
{
    use ResolvesOwned;

    public function __invoke(StoreRelativeFutureReminderRequest $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $user = $this->user($request);

        CreateRelativeFutureReminder::run(
            $user,
            $this->owned($request, $calendarEvent),
            $request->offsetSeconds(),
            $request->message(),
        );

        return back();
    }
}
