<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\Calendar\CalendarEventDraftData;
use App\Models\User;
use Carbon\CarbonImmutable;

/** Read-only and narrow, so adding a provider never reaches into the domain. */
interface CalendarSource
{
    public function name(): string;

    /**
     * A source may answer loosely around the window; the sync keeps what starts inside it.
     *
     * @return list<CalendarEventDraftData>
     */
    public function between(User $user, CarbonImmutable $from, CarbonImmutable $until): array;
}
