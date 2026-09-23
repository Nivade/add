<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/** Opted into by the person, and read before it is kept, so an address that is not a calendar is refused while they are there. */
final class ConnectCalendarFeed
{
    use AsObject;

    public function handle(User $user, string $url): void
    {
        $url = Str::replaceStart('webcal://', 'https://', trim($url));

        DB::transaction(function () use ($user, $url): void {
            // A different feed replaces the old one, which must not linger on home if the new one reads empty.
            if ($user->calendar_feed_url !== null && $user->calendar_feed_url !== $url) {
                DisconnectCalendarFeed::run($user);
            }

            $user->calendar_feed_url = $url;
            $user->save();

            SyncCalendar::run($user);
        });
    }
}
