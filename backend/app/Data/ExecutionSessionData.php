<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\SessionOutcome;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class ExecutionSessionData extends Data
{
    public function __construct(
        public string $id,
        public string $intentionId,
        public ?StepData $currentStep,
        public ?SessionOutcome $outcome,
        public int $stepsCompleted,
        public string $startedAt,
        public ?string $pausedAt,
        public ?string $endedAt,
    ) {}
}
