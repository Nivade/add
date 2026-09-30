<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum CaptureKind: string
{
    case Thought = 'thought';
    case WaitingFor = 'waiting_for';
    case Promise = 'promise';
    case Reminder = 'reminder';
    case NotForYou = 'not_for_you';

    /** @return list<self> */
    public static function answerable(): array
    {
        return [self::Thought, self::WaitingFor, self::Promise, self::Reminder];
    }
}
