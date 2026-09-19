<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Data\RailData;
use App\Enums\IntentionStatus;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use Lorisleiva\Actions\Concerns\AsObject;

/** The rail is on every screen, so it is a shared prop rather than something each page fetches. */
final class BuildRail
{
    use AsObject;

    public function handle(User $user): RailData
    {
        $now = ResolutionContext::forUser($user)->now;

        $session = ExecutionSession::query()->where('user_id', $user->id)->running()->first();

        $next = Intention::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [IntentionStatus::Captured, IntentionStatus::Active])
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '>=', $now)
            ->oldest('deadline_at')
            ->first();

        return new RailData(
            nowAt: $now->toIso8601String(),
            minuteOfDay: $now->hour * 60 + $now->minute,
            sessionStartedAt: $session?->started_at->setTimezone($now->getTimezone())->toIso8601String(),
            deadlineAt: $next?->deadline_at?->setTimezone($now->getTimezone())->toIso8601String(),
            deadlineTitle: $next?->title,
        );
    }
}
