<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\NextActionResolver;
use App\Data\NextActionData;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShowNextActionController extends Controller
{
    public function __invoke(Request $request, NextActionResolver $resolver): NextActionData|JsonResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        // Nothing to do is an answer, not an error: a cold launch reads this first.
        return $resolver->resolve($user, ResolutionContext::forUser($user))
            ?? new JsonResponse('null', 200, [], 0, json: true);
    }
}
