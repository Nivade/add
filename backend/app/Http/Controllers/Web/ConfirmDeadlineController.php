<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Intentions\ConfirmDeadline;
use App\Concerns\ResolvesOwnedIntention;
use App\Http\Controllers\Controller;
use App\Models\Intention;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ConfirmDeadlineController extends Controller
{
    use ResolvesOwnedIntention;

    public function __invoke(Request $request, Intention $intention): RedirectResponse
    {
        ConfirmDeadline::run($this->ownedIntention($request, $intention));

        return back();
    }
}
