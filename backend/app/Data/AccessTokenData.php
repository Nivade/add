<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The plain-text token, which is readable this once and never again. */
#[TypeScript]
class AccessTokenData extends Data
{
    public function __construct(
        public string $token,
        public string $deviceName,
    ) {}
}
