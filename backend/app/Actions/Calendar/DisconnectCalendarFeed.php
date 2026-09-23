<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\AppointmentKind;
use App\Models\CalendarEvent;
use App\Models\Reminder;
use App\Models\User;
use App\Support\Calendar\Sources\IcsCalendarSource;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

/** Everything the feed told us goes with it: a day planned around a calendar they took back is not theirs. */
final class DisconnectCalendarFeed
{
    use AsObject;

    public function handle(User $user): User
    {
        DB::transaction(function () use ($user): void {
            $events = CalendarEvent::query()
                ->where('user_id', $user->id)
                ->where('source', IcsCalendarSource::NAME);

            Reminder::query()
                ->where('user_id', $user->id)
                ->where('appointment_kind', AppointmentKind::CalendarEvent)
                ->whereIn('appointment_id', $events->clone()->select('id'))
                ->delete();

            $events->delete();

            $user->calendar_feed_url = null;
            $user->save();
        });

        return $user;
    }
}
