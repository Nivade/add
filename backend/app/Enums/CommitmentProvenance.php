<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** §21's three-way distinction: what the person did versus what the system guessed. */
#[TypeScript]
enum CommitmentProvenance: string
{
    case UserTask = 'user_task';
    case UserStated = 'user_stated';
    case SystemInferred = 'system_inferred';

    public function isInferred(): bool
    {
        return $this === self::SystemInferred;
    }
}
