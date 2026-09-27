<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Intentions\SetIntentionRecurrence;
use App\Data\IntentionData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetIntentionRecurrenceRequest;
use App\Models\Intention;

final class SetIntentionRecurrenceController extends Controller
{
    use ResolvesOwned;

    public function __invoke(SetIntentionRecurrenceRequest $request, Intention $intention): IntentionData
    {
        return IntentionData::from(SetIntentionRecurrence::run($this->owned($request, $intention), $request->everyDays()));
    }
}
