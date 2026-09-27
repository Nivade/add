<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Confirm only answers an inferred commitment; keep and release end any open one. */
#[TypeScript]
enum CommitmentResponse: string
{
    case Confirm = 'confirm';
    case Keep = 'keep';
    case Release = 'release';
}
