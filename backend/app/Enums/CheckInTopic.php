<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum CheckInTopic: string
{
    case Overwhelm = 'overwhelm';
    case Remembering = 'remembering';

    public function other(): self
    {
        return match ($this) {
            self::Overwhelm => self::Remembering,
            self::Remembering => self::Overwhelm,
        };
    }
}
