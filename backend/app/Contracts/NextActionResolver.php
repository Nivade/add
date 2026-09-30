<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\NextActionData;
use App\Models\User;
use App\Support\NextAction\ChainedNextActionResolver;
use App\Support\NextAction\Decision;
use App\Support\NextAction\ResolutionContext;
use Illuminate\Container\Attributes\Bind;

/** The single most useful step with its `why`, or none; neither method writes. */
#[Bind(ChainedNextActionResolver::class)]
interface NextActionResolver
{
    public function resolve(User $user, ResolutionContext $context): ?NextActionData;

    public function decide(User $user, ResolutionContext $context): ?Decision;
}
