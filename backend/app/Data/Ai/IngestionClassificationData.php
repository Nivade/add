<?php

declare(strict_types=1);

namespace App\Data\Ai;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Symfony\Component\HttpFoundation\Response;

#[TypeScript]
class IngestionClassificationData extends Data
{
    public function __construct(
        public bool $actionable,
        public ?string $title,
        public ?string $why,
        public ?CarbonImmutable $deadlineAt,
        public ?int $estimatedSeconds,
    ) {}

    /** Answers a POST that creates nothing. */
    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
