<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum Place: string
{
    case Home = 'home';
    case Work = 'work';
    case Out = 'out';
    case Computer = 'computer';

    /** A location excludes the other locations; a computer can sit alongside any of them. */
    public function isLocation(): bool
    {
        return $this !== self::Computer;
    }

    public function seemsHere(): string
    {
        return match ($this) {
            self::Home => 'You seem to be at home, where this gets done.',
            self::Work => 'You seem to be at work, where this gets done.',
            self::Out => 'You seem to be out, which this needs.',
            self::Computer => 'You seem to be at a computer, which this needs.',
        };
    }
}
