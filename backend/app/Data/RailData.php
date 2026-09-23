<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Minutes past midnight in the person's zone, so the rail never does zone arithmetic in a browser that sits somewhere else. */
#[TypeScript]
class RailData extends Data
{
    public function __construct(
        public int $nowMinute,
        public ?int $sessionStartedMinute,
        public ?int $leaveByMinute,
        public ?string $leaveByClock,
        public ?string $appointmentTitle,
    ) {}
}
