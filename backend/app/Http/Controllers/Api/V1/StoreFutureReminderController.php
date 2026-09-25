<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\FutureReminders\CreateFutureReminder;
use App\Data\FutureReminderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFutureReminderRequest;

final class StoreFutureReminderController extends Controller
{
    public function __invoke(StoreFutureReminderRequest $request): FutureReminderData
    {
        $user = $this->user($request);

        return FutureReminderData::from(CreateFutureReminder::run($user, $request->text()));
    }
}
