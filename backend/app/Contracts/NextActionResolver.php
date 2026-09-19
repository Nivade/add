<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\NextActionData;
use App\Models\User;
use App\Support\NextAction\ChainedNextActionResolver;
use App\Support\NextAction\ResolutionContext;
use Illuminate\Container\Attributes\Bind;

/**
 * The single most useful step, or none. It never writes, and the `why` is
 * populated whenever a step is.
 */
#[Bind(ChainedNextActionResolver::class)]
interface NextActionResolver
{
    public function resolve(User $user, ResolutionContext $context): ?NextActionData;
}
