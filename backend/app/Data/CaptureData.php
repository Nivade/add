<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CaptureSource;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class CaptureData extends Data
{
    public function __construct(
        public string $id,
        public string $body,
        public CaptureSource $source,
        public ?string $intentionId,
        public string $createdAt,
    ) {}
}
