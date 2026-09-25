<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowAiController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('settings/ai', [
            'consented' => $request->user()->hasConsentedToAi(),
        ]);
    }
}
