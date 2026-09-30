<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Home\BuildHome;
use App\Actions\Home\BuildRail;
use App\Data\ExecutionStateData;
use App\Data\RailData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Response;

final class ShowHomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $this->user($request);
        $home = BuildHome::run($user);
        $stepSeconds = $home->session instanceof ExecutionStateData ? null : $home->rightNow?->step->estimatedSeconds;

        return inertia('home', [
            'home' => $home,
            // Overrides the shared strip, so the step on offer is drawn from now to when it would be done.
            'rail' => fn (): RailData => BuildRail::run($user, stepSeconds: $stepSeconds),
        ]);
    }
}
