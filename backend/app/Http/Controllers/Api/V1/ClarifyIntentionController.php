<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Intentions\ClarifyIntention;
use App\Concerns\ResolvesOwned;
use App\Data\IntentionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClarifyIntentionRequest;
use App\Models\Intention;

final class ClarifyIntentionController extends Controller
{
    use ResolvesOwned;

    public function __invoke(ClarifyIntentionRequest $request, Intention $intention): IntentionData
    {
        return IntentionData::from(ClarifyIntention::run($this->owned($request, $intention), $request->answer()));
    }
}
