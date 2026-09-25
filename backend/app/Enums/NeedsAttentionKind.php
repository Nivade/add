<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Which shape a needs-attention row came from, so the band can render each one differently. */
#[TypeScript]
enum NeedsAttentionKind: string
{
    case Intention = 'intention';
    case WaitingFor = 'waiting_for';
}
