<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Captures\RecordCapture;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaptureRequest;
use Illuminate\Http\RedirectResponse;

final class StoreCaptureController extends Controller
{
    public function __invoke(StoreCaptureRequest $request): RedirectResponse
    {
        $user = $this->user($request);

        RecordCapture::run($user, $request->string('body')->toString(), $request->source());

        return back()->with('captured', true);
    }
}
