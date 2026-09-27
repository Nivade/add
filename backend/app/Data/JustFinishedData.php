<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The intention a session just completed, offered once more so it can be set to repeat. */
#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class JustFinishedData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public ?int $recurrenceEveryDays,
    ) {}
}
