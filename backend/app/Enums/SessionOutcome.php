<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum SessionOutcome: string
{
    case Continued = 'continued';
    case Completed = 'completed';
    case Stopped = 'stopped';
}
