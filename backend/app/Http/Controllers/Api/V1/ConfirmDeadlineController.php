<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Intentions\ConfirmDeadline;
use App\Concerns\ResolvesOwned;
use App\Data\IntentionData;
use App\Http\Controllers\Controller;
use App\Models\Intention;
use Illuminate\Http\Request;

final class ConfirmDeadlineController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, Intention $intention): IntentionData
    {
        return IntentionData::from(ConfirmDeadline::run($this->owned($request, $intention)));
    }
}
