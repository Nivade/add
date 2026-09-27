<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\FutureReminders\CreateFutureReminder;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFutureReminderRequest;
use Illuminate\Http\RedirectResponse;

final class StoreFutureReminderController extends Controller
{
    public function __invoke(StoreFutureReminderRequest $request): RedirectResponse
    {
        $user = $this->user($request);

        CreateFutureReminder::run($user, $request->text());

        return back();
    }
}
