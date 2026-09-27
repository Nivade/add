<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Kept and released are both endings; neither is scored against the other. */
#[TypeScript]
enum CommitmentStatus: string
{
    case Open = 'open';
    case Kept = 'kept';
    case Released = 'released';
}
