<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CommitmentProvenance;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class CommitmentData extends Data
{
    public function __construct(
        public string $id,
        public string $description,
        public CommitmentProvenance $provenance,
        public ?CarbonImmutable $confirmedAt,
    ) {}
}
