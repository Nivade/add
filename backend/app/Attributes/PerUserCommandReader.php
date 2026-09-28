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
        $attribute = new ReflectionClass($class)->getAttributes(PerUserCommand::class)[0] ?? null;

        if ($attribute === null) {
            throw new LogicException("{$class} uses QueuesPerUser but carries no #[PerUserCommand] attribute.");
        }

        return $attribute->newInstance();
    }
}
