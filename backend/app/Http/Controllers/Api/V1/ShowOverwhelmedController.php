<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Overwhelm\ReduceToOneStep;
use App\Data\OverwhelmedData;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use Illuminate\Http\Request;

final class ShowOverwhelmedController extends Controller
{
    public function __invoke(Request $request): OverwhelmedData
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return ReduceToOneStep::run($user, ResolutionContext::forUser($user));
    }
}
