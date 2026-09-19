<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final class ShowWelcomeController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        // Somebody signed in has an answer waiting; the pitch is for everyone else.
        return $request->user() === null ? inertia('welcome') : to_route('home');
    }
}
