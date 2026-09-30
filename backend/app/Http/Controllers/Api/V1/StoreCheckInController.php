<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CheckIns\RecordCheckIn;
use App\Enums\CheckInTopic;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckInRequest;
use Illuminate\Http\Response;

final class StoreCheckInController extends Controller
{
    public function __invoke(StoreCheckInRequest $request, CheckInTopic $topic): Response
    {
        RecordCheckIn::run($this->user($request), $topic, $request->answer());

        return response()->noContent();
    }
}
