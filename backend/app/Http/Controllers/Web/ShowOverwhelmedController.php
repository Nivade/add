<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Overwhelm\ReduceToOneStep;
use App\Http\Controllers\Controller;
use App\Support\NextAction\ResolutionContext;
use Illuminate\Http\Request;
use Inertia\Response;

final class ShowOverwhelmedController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $this->user($request);

        return inertia('overwhelmed', [
            'overwhelmed' => ReduceToOneStep::run($user, ResolutionContext::forUser($user)),
        ]);
    }
}
