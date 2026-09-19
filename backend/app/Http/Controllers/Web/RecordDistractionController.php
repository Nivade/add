<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\RecordDistraction;
use App\Concerns\ResolvesOwnedSession;
use App\Http\Controllers\Controller;
use App\Models\ExecutionSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RecordDistractionController extends Controller
{
    use ResolvesOwnedSession;

    public function __invoke(Request $request, ExecutionSession $session): RedirectResponse
    {
        RecordDistraction::run($this->ownedSession($request, $session));

        return back();
    }
}
