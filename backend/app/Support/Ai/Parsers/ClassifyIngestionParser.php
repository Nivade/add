<?php

declare(strict_types=1);

namespace App\Support\Ai\Parsers;

use App\Data\Ai\IngestionClassificationData;
use App\Support\Ai\Parsers\Concerns\ParsesAiFields;
use Nvade\AiToolkit\Exceptions\AiResponseInvalid;

/** The parser is the validator: the provider hands over decoded JSON and forms no opinion about it. */
final class ClassifyIngestionParser
{
    use ParsesAiFields;

    /** @param  array<string, mixed>  $payload */
    public function parse(array $payload, string $timezone): IngestionClassificationData
    {
        $actionable = $payload['actionable'] ?? null;

        if (! is_bool($actionable)) {
            throw new AiResponseInvalid('classify_ingestion returned a non-boolean actionable.');
        }

        $title = $this->optionalText($payload['title'] ?? null, 'title', 'classify_ingestion');

        if ($actionable && $title === null) {
            throw new AiResponseInvalid('classify_ingestion marked actionable with no title.');
        }

        $estimatedSeconds = $payload['estimated_seconds'] ?? null;

        if ($estimatedSeconds !== null && ! is_int($estimatedSeconds)) {
            throw new AiResponseInvalid('classify_ingestion returned a non-integer estimated_seconds.');
        }

        return new IngestionClassificationData(
            actionable: $actionable,
            title: $title,
            why: $this->optionalText($payload['why'] ?? null, 'why', 'classify_ingestion'),
            deadlineAt: $this->deadline($payload['deadline_at'] ?? null, $timezone, 'classify_ingestion'),
            estimatedSeconds: $estimatedSeconds,
        );
    }
}
