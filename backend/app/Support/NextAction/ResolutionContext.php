<?php

declare(strict_types=1);

namespace App\Support\NextAction;

use App\Models\User;
use Carbon\CarbonImmutable;

/** $now carries the person's zone, so backwards planning from a deadline lands on their day. */
final readonly class ResolutionContext
{
    public function __construct(public CarbonImmutable $now) {}

    public static function forUser(User $user): self
    {
        return new self(CarbonImmutable::now($user->timezone));
    }
}
