<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\StepStatus;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class StepData extends Data
{
    public function __construct(
        public string $id,
        public string $intentionId,
        public string $title,
        public int $position,
        public ?int $estimatedSeconds,
        public StepStatus $status,
        public int $skipCount,
        public bool $generated,
    ) {}
}
