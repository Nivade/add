<?php

declare(strict_types=1);

namespace App\Support\Ai\Parsers;

use App\Data\Ai\IngestionClassificationData;
use App\Support\Ai\Exceptions\AiResponseInvalid;
use Carbon\CarbonImmutable;
use Throwable;

/** The parser is the validator: the provider hands over decoded JSON and forms no opinion about it. */
final class ClassifyIngestionParser
{
    /** @param  array<string, mixed>  $payload */
    public function parse(array $payload, string $timezone): IngestionClassificationData
    {
        $actionable = $payload['actionable'] ?? null;

        if (! is_bool($actionable)) {
            throw new AiResponseInvalid('classify_ingestion returned a non-boolean actionable.');
        }

        $title = $payload['title'] ?? null;

        if ($title !== null && ! is_string($title)) {
            throw new AiResponseInvalid('classify_ingestion returned a non-string title.');
        }

        if ($actionable && (! is_string($title) || trim($title) === '')) {
            throw new AiResponseInvalid('classify_ingestion marked actionable with no title.');
        }

        $why = $payload['why'] ?? null;

        if ($why !== null && ! is_string($why)) {
            throw new AiResponseInvalid('classify_ingestion returned a non-string why.');
        }

        $estimatedSeconds = $payload['estimated_seconds'] ?? null;

        if ($estimatedSeconds !== null && ! is_int($estimatedSeconds)) {
            throw new AiResponseInvalid('classify_ingestion returned a non-integer estimated_seconds.');
        }

        return new IngestionClassificationData(
            actionable: $actionable,
            title: is_string($title) && trim($title) !== '' ? trim($title) : null,
            why: is_string($why) && trim($why) !== '' ? trim($why) : null,
            deadlineAt: $this->deadline($payload['deadline_at'] ?? null, $timezone),
            estimatedSeconds: $estimatedSeconds,
        );
    }

    /** An answer carrying its own offset keeps it; a naive one means the clock the person reads. */
    private function deadline(mixed $value, string $timezone): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '' || strtolower(trim($value)) === 'null') {
            return null;
        }

        try {
            return CarbonImmutable::parse(trim($value), $timezone);
        } catch (Throwable $exception) {
            throw new AiResponseInvalid('classify_ingestion returned an unreadable deadline_at: '.trim($value), previous: $exception);
        }
    }
}
