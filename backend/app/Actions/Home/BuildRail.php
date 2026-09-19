<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Data\RailData;
use App\Models\ExecutionSession;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use App\Support\Time\NextAppointment;
use Lorisleiva\Actions\Concerns\AsObject;

/** The rail is on every screen, so it is a shared prop rather than something each page fetches. */
final class BuildRail
{
    use AsObject;

    public function handle(User $user): RailData
    {
        $now = ResolutionContext::forUser($user)->now;

        $session = ExecutionSession::query()->where('user_id', $user->id)->running()->first();

        $next = NextAppointment::forUser($user, $now);

        return new RailData(
            nowAt: $now->toIso8601String(),
            minuteOfDay: $now->hour * 60 + $now->minute,
            sessionStartedAt: $session?->started_at->setTimezone($now->getTimezone())->toIso8601String(),
            deadlineAt: $next?->appointmentAt()?->setTimezone($now->getTimezone())->toIso8601String(),
            deadlineTitle: $next?->appointmentTitle(),
        );
    }
}
