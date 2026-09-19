<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Time\ExtractedDeadline;
use App\Support\Time\PhraseDeadlineExtractor;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Singleton;

/** $now is passed in rather than read, so a caller can pin it without pinning the clock. */
#[Bind(PhraseDeadlineExtractor::class)]
#[Singleton]
interface DeadlineExtractor
{
    public function extract(string $text, CarbonImmutable $now): ?ExtractedDeadline;
}
