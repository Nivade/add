<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use ReflectionClass;

/** laravel-actions reads its own `command*` properties, never the framework's console attributes. */
trait ReadsCommandAttributes
{
    public static function getCommandSignature(): string
    {
        $signature = new ReflectionClass(static::class)->getAttributes(Signature::class)[0] ?? null;

        return $signature?->newInstance()->signature ?? static::class;
    }

    public static function getCommandDescription(): string
    {
        $description = new ReflectionClass(static::class)->getAttributes(Description::class)[0] ?? null;

        return $description?->newInstance()->description ?? '';
    }
}
