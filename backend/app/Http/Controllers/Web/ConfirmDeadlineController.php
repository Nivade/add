<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Intentions\ConfirmDeadline;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Models\Intention;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ConfirmDeadlineController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, Intention $intention): RedirectResponse
    {
        ConfirmDeadline::run($this->owned($request, $intention));

        return back();
    }
}
