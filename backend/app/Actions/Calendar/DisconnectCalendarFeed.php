<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Support\Calendar\Sources\IcsCalendarSource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

/** Everything the feed told us goes with it: a day planned around a calendar they took back is not theirs. */
final class DisconnectCalendarFeed
{
    use AsObject;

    public function handle(User $user): void
    {
        if ($user->calendar_feed_url !== null) {
            Cache::forget(IcsCalendarSource::cacheKey($user->calendar_feed_url));
        }

        DB::transaction(function () use ($user): void {
            CalendarEvent::forget(CalendarEvent::query()
                ->where('user_id', $user->id)
                ->where('source', IcsCalendarSource::NAME));

            $user->calendar_feed_url = null;
            $user->save();
        });
    }
}
