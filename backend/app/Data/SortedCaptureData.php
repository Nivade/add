<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CaptureKind;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** What the app sorted a capture into, read back until the person answers it. */
#[TypeScript]
class SortedCaptureData extends Data
{
    public function __construct(
        public string $id,
        public string $excerpt,
        public CaptureKind $kind,
        public ?string $detail,
    ) {}
}
