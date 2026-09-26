<?php

declare(strict_types=1);

namespace App\Actions\Ingestion;

use App\Contracts\IngestionSource;
use App\Data\Ai\IngestionClassificationData;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsObject;

/** Thin: the source does the work, this just gives it a caller. */
final class ClassifyPastedText
{
    use AsObject;

    public function __construct(
        private readonly IngestionSource $source,
    ) {}

    public function handle(User $user, string $text): IngestionClassificationData
    {
        return $this->source->classify($user, $text);
    }
}
