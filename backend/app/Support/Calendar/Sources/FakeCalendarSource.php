<?php

declare(strict_types=1);

namespace App\Support\Calendar\Sources;

use App\Contracts\CalendarSource;
use App\Data\Calendar\CalendarEventDraftData;
use App\Models\User;
use Carbon\CarbonImmutable;

/** Pushed events for tests, so no file has to exist on disk. */
final class FakeCalendarSource implements CalendarSource
{
    /** @var list<CalendarEventDraftData> */
    private array $events = [];

    public function push(CalendarEventDraftData ...$events): self
    {
        foreach ($events as $event) {
            $this->events[] = $event;
        }

        return $this;
    }

    public function name(): string
    {
        return 'fake';
    }

    public function between(User $user, CarbonImmutable $from, CarbonImmutable $until): array
    {
        return $this->events;
    }
}
