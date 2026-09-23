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
use LogicException;
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

        $events = $this->store($user, $drafts);

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
            ->whereBetween('starts_at', [$from, $until])
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

    /**
     * One statement for the whole day, matched on what the source calls each event.
     *
     * @param  list<CalendarEventDraftData>  $drafts
     * @return list<CalendarEvent>
     */
    private function store(User $user, array $drafts): array
    {
        $source = $this->source->name();
        $rows = [];

        foreach ($drafts as $draft) {
            $rows[$draft->externalId] = [
                'id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'source' => $source,
                'external_id' => $draft->externalId,
                'title' => Str::limit($draft->title, self::COLUMN_WIDTH),
                'location' => $draft->location === null ? null : Str::limit($draft->location, self::COLUMN_WIDTH),
                'starts_at' => $draft->startsAt,
                'ends_at' => $draft->endsAt,
            ];
        }

        if ($rows === []) {
            return [];
        }

        CalendarEvent::query()->upsert(
            array_values($rows),
            ['user_id', 'source', 'external_id'],
            ['title', 'location', 'starts_at', 'ends_at'],
        );

        $stored = CalendarEvent::query()
            ->where('user_id', $user->id)
            ->where('source', $source)
            ->whereIn('external_id', array_keys($rows))
            ->get()
            ->keyBy('external_id');

        return array_map(
            fn (int|string $externalId): CalendarEvent => $stored->get((string) $externalId) ?? throw new LogicException("Event {$externalId} was not stored."),
            array_keys($rows),
        );
    }
}
