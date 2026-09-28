<?php

declare(strict_types=1);

namespace App\Support\Calendar\Sources;

use App\Attributes\Driver;
use App\Contracts\CalendarSource;
use App\Data\Calendar\CalendarEventDraftData;
use App\Models\User;
use App\Support\Concerns\NamedByDriver;
use Carbon\CarbonImmutable;

/** Pushed events for tests, so no file has to exist on disk. */
#[Driver('fake')]
final class FakeCalendarSource implements CalendarSource
{
    use NamedByDriver;

    /** @var list<CalendarEventDraftData> */
    private array $events = [];

    public function push(CalendarEventDraftData ...$events): self
    {
        foreach ($events as $event) {
            $this->events[] = $event;
        }

        return $this;
    }

    /**
     * @return list<CalendarEventDraftData>
     */
    public function between(User $user, CarbonImmutable $from, CarbonImmutable $until): array
    {
        return $this->events;
    }
}
