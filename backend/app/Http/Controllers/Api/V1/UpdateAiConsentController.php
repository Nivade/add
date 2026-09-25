<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Ai\UpdateAiConsent;
use App\Data\AiConsentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AiConsentUpdateRequest;

final class UpdateAiConsentController extends Controller
{
    public function __invoke(AiConsentUpdateRequest $request): AiConsentData
    {
        $user = $this->user($request);

        UpdateAiConsent::run($user, $request->boolean('consented'));

        return new AiConsentData(consented: $user->ai_consented_at !== null);
    }
}
