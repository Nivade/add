<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\PauseSession;
use App\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Models\ExecutionSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PauseFocusController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, ExecutionSession $session): RedirectResponse
    {
        PauseSession::run($this->owned($request, $session));

        return back();
    }
}
