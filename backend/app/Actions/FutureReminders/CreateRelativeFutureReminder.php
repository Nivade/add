<?php

declare(strict_types=1);

namespace App\Actions\FutureReminders;

use App\Models\CalendarEvent;
use App\Models\FutureReminder;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsObject;

/** §15, calendar-context trigger: no parsing, the person picked the event and the offset. */
final class CreateRelativeFutureReminder
{
    use AsObject;

    public function handle(User $user, CalendarEvent $event, int $offsetSeconds, string $message): FutureReminder
    {
        return FutureReminder::query()->create([
            'user_id' => $user->id,
            'message' => trim($message),
            'calendar_event_id' => $event->id,
            'offset_seconds' => $offsetSeconds,
        ]);
    }
}
