<?php

declare(strict_types=1);

namespace App\Data\Attributes;

use Attribute;

/** Marks Data that must answer with one step, never a list. */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class OneThing
{
    public function __construct() {}
}
