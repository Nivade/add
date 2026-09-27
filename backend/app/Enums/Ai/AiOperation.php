<?php

declare(strict_types=1);

namespace App\Enums\Ai;

enum AiOperation: string
{
    case ParseCapture = 'parse_capture';
    case DecomposeIntention = 'decompose_intention';
    case SplitStep = 'split_step';
    case ClassifyIngestion = 'classify_ingestion';
}
