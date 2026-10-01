<?php

declare(strict_types=1);

namespace App\Data\Ai;

use App\Enums\CaptureKind;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class ParsedCaptureData extends Data
{
    public function __construct(
        public string $title,
        public ?string $why,
        public ?CarbonImmutable $deadlineAt,
        public ?string $clarifyingQuestion,
        public CaptureKind $kind,
        public ?string $waitingOn,
    ) {}
}
