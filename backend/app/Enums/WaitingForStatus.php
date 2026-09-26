<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum WaitingForStatus: string
{
    case Waiting = 'waiting';
    case FollowedUp = 'followed_up';
    case Cancelled = 'cancelled';
    case Received = 'received';

    public function isOpen(): bool
    {
        return $this === self::Waiting || $this === self::FollowedUp;
    }
}
