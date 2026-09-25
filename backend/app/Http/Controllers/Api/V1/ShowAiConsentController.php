<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\AiConsentData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

final class ShowAiConsentController extends Controller
{
    public function __invoke(Request $request): AiConsentData
    {
        return new AiConsentData(consented: $this->user($request)->hasConsentedToAi());
    }
}
