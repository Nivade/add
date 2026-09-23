<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Concerns\QueuesPerUser;
use App\Contracts\CalendarSource;
use App\Data\Calendar\CalendarEventDraftData;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Support\Calendar\Sources\IcsCalendarSource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
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

    private const int COLUMN_WIDTH = 250;

    public string $commandSignature = 'calendar:sync {user? : the id of one person, or every person when omitted}';

    public string $commandDescription = 'Read the days ahead off the connected calendar.';

    public function __construct(private readonly CalendarSource $source) {}

    /** @return list<CalendarEvent> */
    public function handle(User $user, ?CarbonImmutable $now = null): array
    {
        $now ??= $user->now();
        $from = $now->startOfDay();
        $until = $from->addDays((int) config('calendar.horizon_days'))->endOfDay();

        $drafts = array_values(array_filter(
            $this->source->between($user, $from, $until),
            fn (CalendarEventDraftData $draft): bool => $draft->startsAt->between($from, $until),
        ));

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
        CalendarEvent::forget(CalendarEvent::query()
            ->where('user_id', $user->id)
            ->where('source', $this->source->name())
            ->whereBetween('starts_at', [$from->utc(), $until->utc()])
            ->whereNotIn('external_id', array_map(
                fn (CalendarEventDraftData $draft): string => $draft->externalId,
                $drafts,
            )));

        return $events;
    }

    /**
     * Only a feed someone pasted can be read, so nobody else is queued a sync that asks nothing.
     *
     * @param  Builder<User>  $query
     */
    protected function constrainQueued(Builder $query): void
    {
        if ($this->source->name() === IcsCalendarSource::NAME) {
            $query->whereNotNull('calendar_feed_url');
        }
    }

    private function store(User $user, CalendarEventDraftData $draft): CalendarEvent
    {
        $event = CalendarEvent::query()->firstOrNew([
            'user_id' => $user->id,
            'source' => $this->source->name(),
            'external_id' => $draft->externalId,
        ]);

        $event->fill([
            'title' => Str::limit($draft->title, self::COLUMN_WIDTH),
            'location' => $draft->location === null ? null : Str::limit($draft->location, self::COLUMN_WIDTH),
            'starts_at' => $draft->startsAt->utc(),
            'ends_at' => $draft->endsAt?->utc(),
        ])->save();

        return $event;
    }
}
