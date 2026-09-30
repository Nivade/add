<?php

declare(strict_types=1);

namespace App\Actions\CheckIns;

use App\Enums\CheckInTopic;
use App\Models\CheckIn;
use App\Models\ExecutionSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsObject;

/** Asked only of someone old enough to compare and still using the app, since a question to someone who went quiet is a nag. */
final class DueCheckIn
{
    use AsObject;

    public const int EVERY_DAYS = 14;

    public function handle(User $user, CarbonImmutable $now): ?CheckInTopic
    {
        $since = $now->subDays(self::EVERY_DAYS);

        if ($user->created_at === null || $user->created_at->greaterThan($since)) {
            return null;
        }

        $latest = $user->checkIns()->latest('created_at')->latest('id')->first();

        if ($latest instanceof CheckIn && $latest->created_at->greaterThan($since)) {
            return null;
        }

        $active = ExecutionSession::query()
            ->where('user_id', $user->id)
            ->where('ended_at', '>=', $since)
            ->exists();

        if (! $active) {
            return null;
        }

        return $latest?->topic->other() ?? CheckInTopic::Overwhelm;
    }
}
