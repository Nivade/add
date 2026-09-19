<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\ReportStuck;
use App\Concerns\ResolvesOwnedSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportStuckRequest;
use App\Models\ExecutionSession;
use Illuminate\Http\RedirectResponse;

final class ReportStuckController extends Controller
{
    use ResolvesOwnedSession;

    public function __invoke(ReportStuckRequest $request, ExecutionSession $session): RedirectResponse
    {
        ReportStuck::run(
            $this->ownedSession($request, $session),
            $request->reason(),
            $request->string('note')->value() === '' ? null : $request->string('note')->toString(),
        );

        return back();
    }
}
