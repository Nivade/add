<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Captures\ConfirmCaptureKind;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Models\Capture;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ConfirmCaptureKindController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, Capture $capture): RedirectResponse
    {
        ConfirmCaptureKind::run($this->owned($request, $capture));

        return back();
    }
}
