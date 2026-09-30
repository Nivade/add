<?php

declare(strict_types=1);

namespace App\Data\Metrics;

use Spatie\LaravelData\Data;

/** Numbers only: nothing here can carry a title, a capture or an answer's person. */
class OutcomesData extends Data
{
    /**
     * @param  list<RungOutcomeData>  $rungs
     * @param  list<CheckInOutcomeData>  $checkIns
     */
    public function __construct(
        public IntentionOutcomesData $intentions,
        public SessionOutcomesData $sessions,
        public RecoveryOutcomesData $recovery,
        public int $commitmentsKept,
        public array $rungs,
        public array $checkIns,
    ) {}
}
