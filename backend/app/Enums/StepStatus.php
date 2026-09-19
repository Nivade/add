<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum StepStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
    case Skipped = 'skipped';

    public function isActionable(): bool
    {
        return $this === self::Pending;
    }
}
