<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Home\BuildHome;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Response;

final class ShowHomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $this->user($request);

        return inertia('home', ['home' => BuildHome::run($user)]);
    }
}
