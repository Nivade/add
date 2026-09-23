<?php

declare(strict_types=1);

namespace App\Support\Calendar\Sources;

use App\Contracts\CalendarSource;
use App\Data\Calendar\CalendarEventDraftData;
use App\Models\User;
use App\Support\Calendar\Exceptions\CalendarFeedUnreadable;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Node;
use Sabre\VObject\Property;
use Sabre\VObject\Property\ICalendar\DateTime;
use Sabre\VObject\Property\ICalendar\Duration;
use Sabre\VObject\Reader;
use Throwable;

/** The private feed a person's calendar already publishes: no OAuth, and no way to write back. */
final class IcsCalendarSource implements CalendarSource
{
    public const string NAME = 'ics';

    public function name(): string
    {
        return self::NAME;
    }

    public function between(User $user, CarbonImmutable $from, CarbonImmutable $until): array
    {
        $url = $user->calendar_feed_url;

        if ($url === null) {
            return [];
        }

        $zone = new DateTimeZone($user->timezone);
        $events = [];

        foreach ($this->read($url, $from, $until, $zone)->select('VEVENT') as $event) {
            if (! $event instanceof VEvent) {
                continue;
            }

            $draft = $this->draft($event, $zone);

            if ($draft instanceof CalendarEventDraftData) {
                $events[] = $draft;
            }
        }

        return $events;
    }

    /** What a feed last answered goes when the feed does. */
    public static function forget(string $url): void
    {
        Cache::forget(self::cacheKey($url));
    }

    private static function cacheKey(string $url): string
    {
        return 'calendar-feed:'.hash('sha256', $url);
    }

    /** Nothing thrown from here carries the URL, because the URL is the credential. */
    private function read(string $url, CarbonImmutable $from, CarbonImmutable $until, DateTimeZone $zone): VCalendar
    {
        $body = $this->fetch($url);

        try {
            $calendar = Reader::read($body, Reader::OPTION_FORGIVING);

            // Recurrences, their overrides and every zone are resolved here, to the window a sync asks for.
            $expanded = $calendar instanceof VCalendar ? $calendar->expand($from, $until, $zone) : null;
        } catch (Throwable) {
            $expanded = null;
        }

        return $expanded ?? throw new CalendarFeedUnreadable('The calendar feed is not iCalendar.');
    }

    /** An unchanged feed answers 304, and the window still moves daily, so the last body is kept to expand again. */
    private function fetch(string $url): string
    {
        $key = self::cacheKey($url);
        $cached = $this->remembered($key);

        try {
            $response = Http::timeout(15)
                ->withHeaders($cached === null ? [] : array_filter([
                    'If-None-Match' => $cached['etag'],
                    'If-Modified-Since' => $cached['modified'],
                ], is_string(...)))
                ->get($url);
        } catch (ConnectionException) {
            throw new CalendarFeedUnreadable('The calendar feed could not be reached.');
        }

        if ($cached !== null && $response->status() === 304) {
            return $cached['body'];
        }

        if (! $response->successful()) {
            throw new CalendarFeedUnreadable("The calendar feed answered {$response->status()}.");
        }

        $etag = $response->header('ETag');
        $modified = $response->header('Last-Modified');

        if ($etag !== '' || $modified !== '') {
            Cache::put($key, encrypt([
                'etag' => $etag === '' ? null : $etag,
                'modified' => $modified === '' ? null : $modified,
                'body' => $response->body(),
            ]), now()->addDay());
        }

        return $response->body();
    }

    /**
     * Kept encrypted, because the body is the person's whole calendar, not only the window a sync reads.
     *
     * @return array{etag: ?string, modified: ?string, body: string}|null
     */
    private function remembered(string $key): ?array
    {
        $stored = Cache::get($key);

        try {
            $cached = is_string($stored) ? decrypt($stored) : null;
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($cached) || ! is_string($cached['body'] ?? null)) {
            return null;
        }

        return [
            'etag' => is_string($cached['etag'] ?? null) ? $cached['etag'] : null,
            'modified' => is_string($cached['modified'] ?? null) ? $cached['modified'] : null,
            'body' => $cached['body'],
        ];
    }

    private function draft(VEvent $event, DateTimeZone $zone): ?CalendarEventDraftData
    {
        $uid = $this->text($event->UID);
        $startsAt = $this->instant($event->DTSTART, $zone);

        // An all-day entry has no instant to plan backwards from, and a cancelled one is not happening.
        if ($uid === '' || ! $startsAt instanceof CarbonImmutable || strtoupper($this->text($event->STATUS)) === 'CANCELLED') {
            return null;
        }

        $occurrence = $this->instant($event->select('RECURRENCE-ID')[0] ?? null, new DateTimeZone('UTC'));
        $title = $this->text($event->SUMMARY);
        $location = $this->text($event->LOCATION);

        return new CalendarEventDraftData(
            externalId: $occurrence instanceof CarbonImmutable ? $uid.'@'.$occurrence->format('Ymd\THis\Z') : $uid,
            title: $title === '' ? 'Something on your calendar' : $title,
            startsAt: $startsAt,
            endsAt: $this->instant($event->DTEND, $zone) ?? $this->lasting($event, $startsAt),
            location: $location === '' ? null : $location,
        );
    }

    private function lasting(VEvent $event, CarbonImmutable $startsAt): ?CarbonImmutable
    {
        $duration = $event->DURATION;

        return $duration instanceof Duration ? $startsAt->add($duration->getDateInterval()) : null;
    }

    private function text(?Property $property): string
    {
        return $property instanceof Property ? trim((string) $property) : '';
    }

    private function instant(?Node $property, DateTimeZone $zone): ?CarbonImmutable
    {
        if (! $property instanceof DateTime || ! $property->hasTime()) {
            return null;
        }

        $instant = $property->getDateTime($zone);

        return $instant instanceof DateTimeImmutable ? CarbonImmutable::instance($instant) : null;
    }
}
