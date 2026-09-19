<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Which surface an appointment came from, so the screen can say whose word it is. */
#[TypeScript]
enum AppointmentKind: string
{
    case Intention = 'intention';
    case CalendarEvent = 'calendar_event';
}
