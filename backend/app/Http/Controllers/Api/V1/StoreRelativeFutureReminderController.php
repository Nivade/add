<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\FutureReminders\CreateRelativeFutureReminder;
use App\Data\FutureReminderData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRelativeFutureReminderRequest;
use App\Models\CalendarEvent;

final class StoreRelativeFutureReminderController extends Controller
{
    use ResolvesOwned;

    public function __invoke(StoreRelativeFutureReminderRequest $request, CalendarEvent $calendarEvent): FutureReminderData
    {
        $user = $this->user($request);

        return FutureReminderData::from(CreateRelativeFutureReminder::run(
            $user,
            $this->owned($request, $calendarEvent),
            $request->offsetSeconds(),
            $request->message(),
        ));
    }
}
