<?php

declare(strict_types=1);

namespace App\Attributes;

use LogicException;
use ReflectionClass;

final readonly class PerUserCommandReader
{
    /** @param  class-string  $class */
    public static function for(string $class): PerUserCommand
    {
        $attribute = self::tryFor($class);

        if (! $attribute instanceof PerUserCommand) {
            throw new LogicException("{$class} uses QueuesPerUser but carries no #[PerUserCommand] attribute.");
        }

        return $attribute;
    }

    /** @param  class-string  $class */
    public static function tryFor(string $class): ?PerUserCommand
    {
        $attribute = new ReflectionClass($class)->getAttributes(PerUserCommand::class)[0] ?? null;

        return $attribute?->newInstance();
    }
}
