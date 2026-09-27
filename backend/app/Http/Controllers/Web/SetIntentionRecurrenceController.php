<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Intentions\SetIntentionRecurrence;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetIntentionRecurrenceRequest;
use App\Models\Intention;
use Illuminate\Http\RedirectResponse;

final class SetIntentionRecurrenceController extends Controller
{
    use ResolvesOwned;

    public function __invoke(SetIntentionRecurrenceRequest $request, Intention $intention): RedirectResponse
    {
        SetIntentionRecurrence::run($this->owned($request, $intention), $request->everyDays());

        return back();
    }
}
