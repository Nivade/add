<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Ai\UpdateAiConsent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AiConsentUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class UpdateAiConsentController extends Controller
{
    public function __invoke(AiConsentUpdateRequest $request): RedirectResponse
    {
        UpdateAiConsent::run($request->user(), $request->boolean('consented'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI settings updated.')]);

        return to_route('ai.edit');
    }
}
