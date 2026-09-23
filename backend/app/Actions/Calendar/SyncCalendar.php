<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Concerns\QueuesPerUser;
use App\Contracts\CalendarSource;
use App\Data\Calendar\CalendarEventDraftData;
use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;

/** Read-only in one direction: the calendar tells us the day, and we never write back to it. */
final class SyncCalendar
{
    use AsCommand;
    use AsJob;
    use AsObject;
    use QueuesPerUser;

    public string $commandSignature = 'calendar:sync {user? : the id of one person, or every person when omitted}';

    public string $commandDescription = 'Read the days ahead off the connected calendar.';

    public function __construct(private readonly CalendarSource $source) {}

    /** @return list<CalendarEvent> */
    public function handle(User $user, ?CarbonImmutable $now = null): array
    {
        $now ??= $user->now();
        $from = $now->startOfDay();
        $until = $from->addDays((int) config('calendar.horizon_days'))->endOfDay();

        $drafts = $this->source->between($user, $from, $until);

        $events = array_map(
            fn (CalendarEventDraftData $draft): CalendarEvent => $this->store($user, $draft),
            $drafts,
        );

        // A read that came back empty is a source that failed, not a day that cleared:
        // it cannot be told from an outage here, and reaping would take stated minutes with it.
        if ($drafts === []) {
            return $events;
        }

        // An event the source stopped reporting was moved or cancelled there, and
        // keeping it would have us plan a day around something nobody is attending.
        CalendarEvent::query()
            ->where('user_id', $user->id)
            ->where('source', $this->source->name())
            ->whereBetween('starts_at', [$from, $until])
            ->whereNotIn('external_id', array_map(
                fn (CalendarEventDraftData $draft): string => $draft->externalId,
                $drafts,
            ))
            ->delete();

        return $events;
    }

    private function store(User $user, CalendarEventDraftData $draft): CalendarEvent
    {
        $event = CalendarEvent::query()->firstOrNew([
            'user_id' => $user->id,
            'source' => $this->source->name(),
            'external_id' => $draft->externalId,
        ]);

        $event->fill([
            'title' => $draft->title,
            'location' => $draft->location,
            'starts_at' => $draft->startsAt->utc(),
            'ends_at' => $draft->endsAt?->utc(),
        ])->save();

        return $event;
    }
}
