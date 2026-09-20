<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ExecutionEventType: string
{
    case Started = 'started';
    case StepCompleted = 'step_completed';
    case StepSkipped = 'step_skipped';
    case StepSplit = 'step_split';
    case Paused = 'paused';
    case Resumed = 'resumed';
    case Stuck = 'stuck';
    case Distracted = 'distracted';
    case Stopped = 'stopped';
}
