<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sessions\ResumeSession;
use App\Concerns\ResolvesOwnedSession;
use App\Data\ExecutionStateData;
use App\Http\Controllers\Controller;
use App\Models\ExecutionSession;
use Illuminate\Http\Request;

final class ResumeSessionController extends Controller
{
    use ResolvesOwnedSession;

    public function __invoke(Request $request, ExecutionSession $session): ExecutionStateData
    {
        return ExecutionStateData::of(ResumeSession::run($this->ownedSession($request, $session)));
    }
}
