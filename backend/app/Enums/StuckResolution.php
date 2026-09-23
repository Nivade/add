<?php

declare(strict_types=1);

namespace App\Enums;

/** What a stuck answer does about it, so a new reason cannot quietly resolve to nothing. */
enum StuckResolution
{
    case Split;
    case NextStep;
    case Stop;
    case StayPut;
}
