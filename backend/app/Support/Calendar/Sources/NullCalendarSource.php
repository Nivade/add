<?php

declare(strict_types=1);

namespace App\Support\Calendar\Sources;

use App\Contracts\CalendarSource;
use App\Data\Calendar\CalendarEventDraftData;
use App\Models\User;
use Carbon\CarbonImmutable;

/** No calendar connected. An empty day is the honest answer, not an error. */
final class NullCalendarSource implements CalendarSource
{
    public function name(): string
    {
        return 'none';
    }

    /** @return list<CalendarEventDraftData> */
    public function between(User $user, CarbonImmutable $from, CarbonImmutable $until): array
    {
        return [];
    }
}
