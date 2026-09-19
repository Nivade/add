<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The three things that happen before an appointment, in the order they happen. */
#[TypeScript]
enum PlanRung: string
{
    case FindThings = 'find_things';
    case GetReady = 'get_ready';
    case Leave = 'leave';

    public function column(): string
    {
        return match ($this) {
            self::FindThings => 'gathering_seconds',
            self::GetReady => 'preparation_seconds',
            self::Leave => 'travel_seconds',
        };
    }

    public function assumedSeconds(): int
    {
        return match ($this) {
            self::FindThings => 600,
            self::GetReady => 1200,
            self::Leave => 1800,
        };
    }
}
