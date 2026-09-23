<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowCalendarController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $url = $request->user()->calendar_feed_url;

        // The host is enough to recognise it; the rest of the URL is the credential and stays on the server.
        return Inertia::render('settings/calendar', [
            'connectedHost' => $url === null ? null : parse_url($url, PHP_URL_HOST),
        ]);
    }
}
