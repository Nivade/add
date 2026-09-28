<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use App\Attributes\Driver;

trait NamedByDriver
{
    public function name(): string
    {
        return Driver::nameOf(static::class);
    }
}
