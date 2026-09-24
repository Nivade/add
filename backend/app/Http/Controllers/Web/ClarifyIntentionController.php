<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Intentions\ClarifyIntention;
use App\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClarifyIntentionRequest;
use App\Models\Intention;
use Illuminate\Http\RedirectResponse;

final class ClarifyIntentionController extends Controller
{
    use ResolvesOwned;

    public function __invoke(ClarifyIntentionRequest $request, Intention $intention): RedirectResponse
    {
        ClarifyIntention::run($this->owned($request, $intention), $request->answer());

        return back();
    }
}
