<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum DevicePlatform: string
{
    case Ios = 'ios';
    case Android = 'android';
}
