<?php

declare(strict_types=1);

namespace App\Attributes;

use App\Enums\Cadence;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class PerUserCommand
{
    public function __construct(
        public string $name,
        public string $description,
        public Cadence $every,
    ) {}
}
