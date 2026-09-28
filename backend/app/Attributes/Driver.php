<?php

declare(strict_types=1);

namespace App\Attributes;

use Attribute;
use LogicException;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Driver
{
    public function __construct(public string $name) {}

    /**
     * @param  list<class-string>  $candidates
     * @return ?class-string
     */
    public static function classFor(string $configured, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $attribute = new ReflectionClass($candidate)->getAttributes(self::class)[0] ?? null;

            if ($attribute !== null && $attribute->newInstance()->name === $configured) {
                return $candidate;
            }
        }

        return null;
    }

    /** @param  class-string  $class */
    public static function nameOf(string $class): string
    {
        $attribute = new ReflectionClass($class)->getAttributes(self::class)[0] ?? null;

        if ($attribute === null) {
            throw new LogicException("{$class} has no #[Driver] attribute.");
        }

        return $attribute->newInstance()->name;
    }
}
