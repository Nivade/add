<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Models\User;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/** Opted into by the person, never assumed, and read straight away so home reflects it. */
final class ConnectCalendarFeed
{
    use AsObject;

    public function handle(User $user, string $url): User
    {
        $user->calendar_feed_url = Str::replaceStart('webcal://', 'https://', trim($url));
        $user->save();

        SyncCalendar::dispatch($user);

        return $user;
    }
}
