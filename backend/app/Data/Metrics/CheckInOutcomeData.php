<?php

declare(strict_types=1);

namespace App\Data\Metrics;

use App\Enums\CheckInTopic;
use Spatie\LaravelData\Data;

class CheckInOutcomeData extends Data
{
    public function __construct(
        public CheckInTopic $topic,
        public int $less,
        public int $same,
        public int $more,
        public int $notNow,
    ) {}
}
