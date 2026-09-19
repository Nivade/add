<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum CaptureSource: string
{
    case Text = 'text';
    case Voice = 'voice';
    case Photo = 'photo';
    case Document = 'document';
    case Email = 'email';
    case Url = 'url';
}
