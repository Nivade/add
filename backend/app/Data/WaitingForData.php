<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\WaitingForStatus;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class WaitingForData extends Data
{
    public function __construct(
        public string $id,
        public string $subject,
        public ?string $note,
        public WaitingForStatus $status,
    ) {}
}
