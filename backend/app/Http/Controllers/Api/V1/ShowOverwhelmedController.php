<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Overwhelm\ReduceToOneStep;
use App\Data\OverwhelmedData;
use App\Http\Controllers\Controller;
use App\Support\NextAction\ResolutionContext;
use Illuminate\Http\Request;

final class ShowOverwhelmedController extends Controller
{
    public function __invoke(Request $request): OverwhelmedData
    {
        $user = $this->user($request);

        return ReduceToOneStep::run($user, ResolutionContext::forUser($user));
    }
}
