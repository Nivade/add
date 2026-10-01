<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Captures\ChangeCaptureKind;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeCaptureKindRequest;
use App\Models\Capture;
use Illuminate\Http\RedirectResponse;

final class ChangeCaptureKindController extends Controller
{
    use ResolvesOwned;

    public function __invoke(ChangeCaptureKindRequest $request, Capture $capture): RedirectResponse
    {
        ChangeCaptureKind::run($this->owned($request, $capture), $request->kind());

        return back();
    }
}
