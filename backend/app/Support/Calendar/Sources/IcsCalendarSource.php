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
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
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

    /**
     * Nothing thrown from here carries the URL, because the URL is the credential.
     *
     * @return VCalendar<int, Node>
     */
    private function read(string $url, CarbonImmutable $from, CarbonImmutable $until, DateTimeZone $zone): VCalendar
    {
        try {
            $body = Http::timeout(15)->get($url)->throw()->body();
        } catch (RequestException $exception) {
            throw new CalendarFeedUnreadable("The calendar feed answered {$exception->response->status()}.");
        } catch (ConnectionException) {
            throw new CalendarFeedUnreadable('The calendar feed could not be reached.');
        }

        try {
            $calendar = Reader::read($body, Reader::OPTION_FORGIVING);

            // Recurrences, their overrides and every zone are resolved here, to the window a sync asks for.
            $expanded = $calendar instanceof VCalendar ? $calendar->expand($from, $until, $zone) : null;
        } catch (Throwable) {
            $expanded = null;
        }

        return $expanded ?? throw new CalendarFeedUnreadable('The calendar feed is not iCalendar.');
    }

    /** @param  VEvent<int, Node>  $event */
    private function draft(VEvent $event, DateTimeZone $zone): ?CalendarEventDraftData
    {
        $uid = $this->text($event, 'UID');
        $startsAt = $this->instant($event->__get('DTSTART'), $zone);

        // An all-day entry has no instant to plan backwards from, and a cancelled one is not happening.
        if ($uid === '' || ! $startsAt instanceof CarbonImmutable || strtoupper($this->text($event, 'STATUS')) === 'CANCELLED') {
            return null;
        }

        $occurrence = $this->instant($event->__get('RECURRENCE-ID'), new DateTimeZone('UTC'));
        $title = $this->text($event, 'SUMMARY');
        $location = $this->text($event, 'LOCATION');

        return new CalendarEventDraftData(
            externalId: $occurrence instanceof CarbonImmutable ? $uid.'@'.$occurrence->format('Ymd\THis\Z') : $uid,
            title: $title === '' ? 'Something on your calendar' : $title,
            startsAt: $startsAt,
            endsAt: $this->instant($event->__get('DTEND'), $zone) ?? $this->lasting($event, $startsAt),
            location: $location === '' ? null : $location,
        );
    }

    /** @param  VEvent<int, Node>  $event */
    private function lasting(VEvent $event, CarbonImmutable $startsAt): ?CarbonImmutable
    {
        $duration = $event->__get('DURATION');

        return $duration instanceof Duration ? $startsAt->add($duration->getDateInterval()) : null;
    }

    /** @param  VEvent<int, Node>  $event */
    private function text(VEvent $event, string $name): string
    {
        $property = $event->__get($name);

        return $property instanceof Property ? trim((string) $property) : '';
    }

    /** @param  Node<int, Node>|null  $property */
    private function instant(?Node $property, DateTimeZone $zone): ?CarbonImmutable
    {
        if (! $property instanceof DateTime || ! $property->hasTime()) {
            return null;
        }

        $instant = $property->getDateTime($zone);

        return $instant instanceof DateTimeImmutable ? CarbonImmutable::instance($instant) : null;
    }
}
