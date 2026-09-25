<?php

declare(strict_types=1);

namespace App\Support\Ingestion;

use App\Contracts\AiProvider;
use App\Contracts\IngestionSource;
use App\Data\Ai\IngestionClassificationData;
use App\Models\User;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Parsers\ClassifyIngestionParser;

/** The only adapter this phase builds: a person pastes text, nothing is fetched from anywhere. */
final class ManualIngestionSource implements IngestionSource
{
    public function __construct(
        private readonly AiProvider $provider,
        private readonly ClassifyIngestionParser $parser,
    ) {}

    public function name(): string
    {
        return 'manual';
    }

    public function classify(User $user, string $text): IngestionClassificationData
    {
        return $this->parser->parse(
            $this->provider->complete(AiRequest::classifyIngestion($user->id, $text))->payload,
            $user->timezone,
        );
    }
}
