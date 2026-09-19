<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Overwhelm\ReduceToOneStep;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use Illuminate\Http\Request;
use Inertia\Response;

final class ShowOverwhelmedController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return inertia('overwhelmed', [
            'overwhelmed' => ReduceToOneStep::run($user, ResolutionContext::forUser($user)),
        ]);
    }
}
