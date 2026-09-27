<?php

declare(strict_types=1);

namespace App\Actions\Ingestion;

use App\Contracts\AiProvider;
use App\Data\Ai\IngestionClassificationData;
use App\Models\User;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Parsers\ClassifyIngestionParser;
use Lorisleiva\Actions\Concerns\AsObject;

/** Reads only what the person pasted; nothing is fetched from anywhere. */
final class ClassifyPastedText
{
    use AsObject;

    public function __construct(
        private readonly AiProvider $provider,
        private readonly ClassifyIngestionParser $parser,
    ) {}

    public function handle(User $user, string $text): IngestionClassificationData
    {
        return $this->parser->parse(
            $this->provider->complete(AiRequest::classifyIngestion($user->id, $text))->payload,
            $user->timezone,
        );
    }
}
