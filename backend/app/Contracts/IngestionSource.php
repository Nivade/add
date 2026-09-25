<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\Ai\IngestionClassificationData;
use App\Models\User;

/**
 * Narrow on purpose: a source turns one piece of external text into a
 * classification, and nothing about where the text came from reaches the
 * domain. Adding a provider (email, documents) means a new implementation,
 * never a branch here.
 */
interface IngestionSource
{
    public function name(): string;

    public function classify(User $user, string $text): IngestionClassificationData;
}
