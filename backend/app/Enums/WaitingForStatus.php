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

    /** @return list<self> */
    public static function open(): array
    {
        return [self::Waiting, self::FollowedUp];
    }

    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
    }
}
