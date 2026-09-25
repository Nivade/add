<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\NextActionResolver;
use App\Data\NextActionData;
use App\Http\Controllers\Controller;
use App\Http\Responses\NullAnswer;
use App\Support\NextAction\ResolutionContext;
use Illuminate\Http\Request;

final class ShowNextActionController extends Controller
{
    public function __invoke(Request $request, NextActionResolver $resolver): NextActionData|NullAnswer
    {
        $user = $this->user($request);

        return $resolver->resolve($user, ResolutionContext::forUser($user))
            ?? new NullAnswer;
    }
}
