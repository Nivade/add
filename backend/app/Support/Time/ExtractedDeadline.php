<?php

declare(strict_types=1);

namespace App\Support\Time;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/** $phrase is the text the extractor claimed, so the model is never asked about it again. */
final readonly class ExtractedDeadline
{
    public function __construct(
        public CarbonImmutable $at,
        public string $phrase,
    ) {}

    /** What is left once the claimed phrase is taken out, or the whole text if nothing is. */
    public function remainderOf(string $text, string $alsoTrim = ''): string
    {
        $remainder = trim(Str::squish(str_replace($this->phrase, ' ', $text)), " \t\n\r\0\x0B".$alsoTrim);

        return $remainder === '' ? $text : $remainder;
    }
}
