<?php

declare(strict_types=1);

namespace App\CustomAttributes;

use Attribute;
use Throwable;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class FailOn
{
    /** @var list<class-string<Throwable>> */
    public array $exceptions;

    /** @param  class-string<Throwable>  ...$exceptions */
    public function __construct(string ...$exceptions)
    {
        $this->exceptions = array_values($exceptions);
    }
}
