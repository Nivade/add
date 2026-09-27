<?php

declare(strict_types=1);

namespace App\Support\Ai\Parsers;

use App\Data\Ai\ParsedCaptureData;
use App\Support\Ai\Exceptions\AiResponseInvalid;
use App\Support\Ai\Parsers\Concerns\ParsesAiFields;

/** The parser is the validator: the provider hands over decoded JSON and forms no opinion about it. */
final class ParseCaptureParser
{
    use ParsesAiFields;

    /** @param  array<string, mixed>  $payload */
    public function parse(array $payload, string $timezone): ParsedCaptureData
    {
        $title = $payload['title'] ?? null;

        if (! is_string($title) || trim($title) === '') {
            throw new AiResponseInvalid('parse_capture returned no usable title.');
        }

        if (! array_key_exists('clarifying_question', $payload)) {
            throw new AiResponseInvalid('parse_capture returned no clarifying_question.');
        }

        return new ParsedCaptureData(
            title: trim($title),
            why: $this->optionalText($payload['why'] ?? null, 'why', 'parse_capture'),
            deadlineAt: $this->deadline($payload['deadline_at'] ?? null, $timezone, 'parse_capture'),
            clarifyingQuestion: $this->optionalText($payload['clarifying_question'], 'clarifying_question', 'parse_capture'),
        );
    }
}
