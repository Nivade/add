<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Whereabouts\ReportNotHere;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportNotHereRequest;
use Illuminate\Http\RedirectResponse;

final class ReportNotHereController extends Controller
{
    public function __invoke(ReportNotHereRequest $request): RedirectResponse
    {
        ReportNotHere::run($this->user($request), $request->place());

        return back();
    }
}
