<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The four responses §20 gives the person, once a waiting-for is stale enough to surface. */
#[TypeScript]
enum WaitingForResponse: string
{
    case WaitLonger = 'wait_longer';
    case FollowUp = 'follow_up';
    case Cancel = 'cancel';
    case Receive = 'receive';

    public function status(): WaitingForStatus
    {
        return match ($this) {
            self::WaitLonger => WaitingForStatus::Waiting,
            self::FollowUp => WaitingForStatus::FollowedUp,
            self::Cancel => WaitingForStatus::Cancelled,
            self::Receive => WaitingForStatus::Received,
        };
    }
}
