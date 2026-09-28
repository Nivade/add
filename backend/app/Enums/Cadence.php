<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Console\Scheduling\Event;

enum Cadence
{
    case EveryMinute;
    case Hourly;

    public function apply(Event $event): Event
    {
        return match ($this) {
            self::EveryMinute => $event->everyMinute(),
            self::Hourly => $event->hourly(),
        };
    }
}
