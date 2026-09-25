<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\RecordDistraction;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Models\ExecutionSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RecordDistractionController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, ExecutionSession $session): RedirectResponse
    {
        RecordDistraction::run($this->owned($request, $session));

        return back();
    }
}
