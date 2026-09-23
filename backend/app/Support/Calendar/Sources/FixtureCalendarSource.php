<?php

declare(strict_types=1);

namespace App\Support\Calendar\Sources;

use App\Contracts\CalendarSource;
use App\Data\Calendar\CalendarEventDraftData;
use App\Models\User;
use App\Support\Calendar\Exceptions\CalendarFixtureInvalid;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

/** A day read off disk, so the whole path runs with no provider and nothing leaving the machine. */
final class FixtureCalendarSource implements CalendarSource
{
    public function name(): string
    {
        return 'fixture';
    }

    public function between(User $user, CarbonImmutable $from, CarbonImmutable $until): array
    {
        $path = rtrim((string) config('calendar.fixture_path'), '/').'/'.$user->id.'.json';

        if (! File::exists($path)) {
            return [];
        }

        $decoded = json_decode(File::get($path), true);

        if (! is_array($decoded)) {
            throw new CalendarFixtureInvalid("Calendar fixture {$path} is not a JSON array.");
        }

        $events = [];

        foreach ($decoded as $row) {
            if (! is_array($row)) {
                throw new CalendarFixtureInvalid("Calendar fixture {$path} holds something that is not an event.");
            }

            $events[] = $this->draft($row, $path, $user->timezone);
        }

        return $events;
    }

    /** @param  array<mixed>  $row */
    private function draft(array $row, string $path, string $timezone): CalendarEventDraftData
    {
        foreach (['external_id', 'title', 'starts_at'] as $key) {
            if (! is_string($row[$key] ?? null)) {
                throw new CalendarFixtureInvalid("Calendar fixture {$path} has an event without {$key}.");
            }
        }

        $endsAt = $row['ends_at'] ?? null;
        $location = $row['location'] ?? null;

        return new CalendarEventDraftData(
            externalId: (string) $row['external_id'],
            title: (string) $row['title'],
            startsAt: CarbonImmutable::parse((string) $row['starts_at'], $timezone),
            endsAt: is_string($endsAt) ? CarbonImmutable::parse($endsAt, $timezone) : null,
            location: is_string($location) ? $location : null,
        );
    }
}
