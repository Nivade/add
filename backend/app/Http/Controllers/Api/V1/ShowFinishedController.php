<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sessions\BuildFinished;
use App\Data\FinishedData;
use App\Enums\SessionOutcome;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Models\ExecutionSession;
use Illuminate\Http\Request;

final class ShowFinishedController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, ExecutionSession $session): FinishedData
    {
        abort_unless($this->owned($request, $session)->outcome === SessionOutcome::Completed, 404);

        return BuildFinished::run($session);
    }
}
