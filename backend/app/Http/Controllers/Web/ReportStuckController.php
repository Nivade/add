<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\ReportStuck;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportStuckRequest;
use App\Models\ExecutionSession;
use Illuminate\Http\RedirectResponse;

final class ReportStuckController extends Controller
{
    use ResolvesOwned;

    public function __invoke(ReportStuckRequest $request, ExecutionSession $session): RedirectResponse
    {
        ReportStuck::run(
            $this->owned($request, $session),
            $request->reason(),
            $request->note(),
        );

        return back();
    }
}
