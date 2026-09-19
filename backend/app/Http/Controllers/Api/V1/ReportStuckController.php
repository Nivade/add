<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sessions\ReportStuck;
use App\Concerns\ResolvesOwnedSession;
use App\Data\ExecutionStateData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReportStuckRequest;
use App\Models\ExecutionSession;

final class ReportStuckController extends Controller
{
    use ResolvesOwnedSession;

    public function __invoke(ReportStuckRequest $request, ExecutionSession $session): ExecutionStateData
    {
        return ExecutionStateData::of(ReportStuck::run(
            $this->ownedSession($request, $session),
            $request->reason(),
            $request->string('note')->value() === '' ? null : $request->string('note')->toString(),
        ));
    }
}
