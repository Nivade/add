<?php

declare(strict_types=1);

namespace App\Support\Ai\Parsers;

use App\Data\Ai\ParsedCaptureData;
use App\Support\Ai\Exceptions\AiResponseInvalid;
use App\Support\Ai\Parsers\Concerns\ParsesAiDeadline;

/** The parser is the validator: the provider hands over decoded JSON and forms no opinion about it. */
final class ParseCaptureParser
{
    use ParsesAiDeadline;

    /** @param  array<string, mixed>  $payload */
    public function parse(array $payload, string $timezone): ParsedCaptureData
    {
        $title = $payload['title'] ?? null;

        if (! is_string($title) || trim($title) === '') {
            throw new AiResponseInvalid('parse_capture returned no usable title.');
        }

        $why = $payload['why'] ?? null;

        if ($why !== null && ! is_string($why)) {
            throw new AiResponseInvalid('parse_capture returned a non-string why.');
        }

        if (! array_key_exists('clarifying_question', $payload)) {
            throw new AiResponseInvalid('parse_capture returned no clarifying_question.');
        }

        $question = $payload['clarifying_question'];

        if ($question !== null && ! is_string($question)) {
            throw new AiResponseInvalid('parse_capture returned a non-string clarifying_question.');
        }

        return new ParsedCaptureData(
            title: trim($title),
            why: is_string($why) && trim($why) !== '' ? trim($why) : null,
            deadlineAt: $this->deadline($payload['deadline_at'] ?? null, $timezone, 'parse_capture'),
            clarifyingQuestion: $question !== null && trim($question) !== '' ? trim($question) : null,
        );
    }
}
