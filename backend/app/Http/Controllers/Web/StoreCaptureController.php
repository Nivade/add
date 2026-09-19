<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Captures\RecordCapture;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaptureRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class StoreCaptureController extends Controller
{
    public function __invoke(StoreCaptureRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        RecordCapture::run($user, $request->string('body')->toString(), $request->source());

        return back()->with('captured', true);
    }
}
