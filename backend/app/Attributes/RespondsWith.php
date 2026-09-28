<?php

declare(strict_types=1);

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class RespondsWith
{
    public function __construct(
        public int $status,
        public ?string $message = null,
    ) {}
}
