<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Whereabouts\ReportNotHere;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportNotHereRequest;
use Illuminate\Http\Response;

final class ReportNotHereController extends Controller
{
    public function __invoke(ReportNotHereRequest $request): Response
    {
        ReportNotHere::run($this->user($request), $request->place());

        return response()->noContent();
    }
}
