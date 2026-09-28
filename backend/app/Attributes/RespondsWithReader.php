<?php

declare(strict_types=1);

namespace App\Attributes;

use ReflectionClass;
use Throwable;

/** Most throwables opt out by carrying no attribute at all, so this reads without throwing. */
final readonly class RespondsWithReader
{
    public static function for(Throwable $exception): ?RespondsWith
    {
        $attribute = new ReflectionClass($exception::class)->getAttributes(RespondsWith::class)[0] ?? null;

        return $attribute?->newInstance();
    }
}
