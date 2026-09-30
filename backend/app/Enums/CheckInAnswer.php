<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum CheckInAnswer: string
{
    case Less = 'less';
    case Same = 'same';
    case More = 'more';
    case NotNow = 'not_now';
}
