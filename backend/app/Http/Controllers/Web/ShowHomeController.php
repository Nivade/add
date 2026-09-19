<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Home\BuildHome;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Response;

final class ShowHomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return inertia('home', ['home' => BuildHome::run($user)]);
    }
}
