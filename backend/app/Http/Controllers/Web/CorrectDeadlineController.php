<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Intentions\CorrectDeadline;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\CorrectDeadlineRequest;
use App\Models\Intention;
use Illuminate\Http\RedirectResponse;

final class CorrectDeadlineController extends Controller
{
    use ResolvesOwned;

    public function __invoke(CorrectDeadlineRequest $request, Intention $intention): RedirectResponse
    {
        CorrectDeadline::run($this->owned($request, $intention), $request->deadlineAt($this->user($request)));

        return to_route('home');
    }
}
