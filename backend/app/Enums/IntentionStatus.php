<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum IntentionStatus: string
{
    case Captured = 'captured';
    case Active = 'active';
    case Done = 'done';
    case SetAside = 'set_aside';

    public function isOpen(): bool
    {
        return $this === self::Captured || $this === self::Active;
    }
}
