<?php

declare(strict_types=1);

namespace App\Support\Ai\Parsers;

use App\Data\Ai\ParsedCaptureData;
use App\Support\Ai\Exceptions\AiResponseInvalid;
use Carbon\CarbonImmutable;
use Throwable;

/** The parser is the validator: the provider hands over decoded JSON and forms no opinion about it. */
final class ParseCaptureParser
{
    /** @param  array<string, mixed>  $payload */
    public function parse(array $payload): ParsedCaptureData
    {
        $title = $payload['title'] ?? null;

        if (! is_string($title) || trim($title) === '') {
            throw new AiResponseInvalid('parse_capture returned no usable title.');
        }

        $why = $payload['why'] ?? null;

        if ($why !== null && ! is_string($why)) {
            throw new AiResponseInvalid('parse_capture returned a non-string why.');
        }

        $needsClarification = $payload['needs_clarification'] ?? null;

        if (! is_bool($needsClarification)) {
            throw new AiResponseInvalid('parse_capture returned a non-boolean needs_clarification.');
        }

        return new ParsedCaptureData(
            title: trim($title),
            why: is_string($why) && trim($why) !== '' ? trim($why) : null,
            deadlineAt: $this->deadline($payload['deadline_at'] ?? null),
            needsClarification: $needsClarification,
        );
    }

    private function deadline(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '' || strtolower(trim($value)) === 'null') {
            return null;
        }

        try {
            return CarbonImmutable::parse(trim($value));
        } catch (Throwable $exception) {
            throw new AiResponseInvalid('parse_capture returned an unreadable deadline_at: '.trim($value), previous: $exception);
        }
    }
}
