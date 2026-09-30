<?php

declare(strict_types=1);

namespace App\Support\Ai\Parsers;

use App\Data\Ai\ParsedCaptureData;
use App\Enums\CaptureKind;
use App\Support\Ai\Parsers\Concerns\ParsesAiFields;
use Nvade\AiToolkit\Exceptions\AiResponseInvalid;

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
            kind: $this->kind($payload['kind'] ?? null),
            waitingOn: $this->optionalText($payload['waiting_on'] ?? null, 'waiting_on', 'parse_capture'),
        );
    }

    /** Not-for-you is decided by the ingestion classifier, never by this parse. */
    private function kind(mixed $kind): CaptureKind
    {
        $parsed = is_string($kind) ? CaptureKind::tryFrom($kind) : null;

        if ($parsed === null || ! in_array($parsed, CaptureKind::answerable(), true)) {
            throw new AiResponseInvalid('parse_capture returned an unknown kind.');
        }

        return $parsed;
    }
}
