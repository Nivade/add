<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Sessions\StartSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionRequest;
use App\Models\Step;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class StartFocusController extends Controller
{
    public function __invoke(StoreSessionRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $step = Step::query()->findOrFail($request->string('step_id')->toString());

        abort_unless($step->intention->user_id === $user->id, 404);

        StartSession::run($user, $step);

        return to_route('focus');
    }
}
