<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\IntentionStatus;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class IntentionData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $why,
        public IntentionStatus $status,
        public ?string $deadlineAt,
        public bool $deadlineInferred,
        public ?string $clarifyingQuestion,
    ) {}
}
