<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Intentions\CorrectDeadline;
use App\Data\IntentionData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\CorrectDeadlineRequest;
use App\Models\Intention;

final class CorrectDeadlineController extends Controller
{
    use ResolvesOwned;

    public function __invoke(CorrectDeadlineRequest $request, Intention $intention): IntentionData
    {
        return IntentionData::from(CorrectDeadline::run($this->owned($request, $intention), $request->deadlineAt($this->user($request))));
    }
}
