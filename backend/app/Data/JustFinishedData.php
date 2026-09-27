<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The intention a session just completed, offered once more so it can be set to repeat. */
#[TypeScript]
class JustFinishedData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public ?int $recurrenceEveryDays,
    ) {}
}
