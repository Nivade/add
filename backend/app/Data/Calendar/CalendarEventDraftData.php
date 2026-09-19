<?php

declare(strict_types=1);

namespace App\Data\Calendar;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/** What a source reports before anything of ours is attached to it. */
class CalendarEventDraftData extends Data
{
    public function __construct(
        public string $externalId,
        public string $title,
        public CarbonImmutable $startsAt,
        public ?CarbonImmutable $endsAt = null,
        public ?string $location = null,
    ) {}
}
