<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

final class DestroyTokenController extends Controller
{
    /** Signing out on one device leaves every other device signed in, so only the presented token goes. */
    public function __invoke(Request $request): Response
    {
        PersonalAccessToken::findToken($request->bearerToken() ?? '')?->delete();

        return response()->noContent();
    }
}
