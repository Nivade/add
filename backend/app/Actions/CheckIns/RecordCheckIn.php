<?php

declare(strict_types=1);

namespace App\Actions\CheckIns;

use App\Enums\CheckInAnswer;
use App\Enums\CheckInTopic;
use App\Models\CheckIn;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsObject;

/** Both clients can show the question before either answers, so a second answer in the fortnight keeps the first. */
final class RecordCheckIn
{
    use AsObject;

    public function handle(User $user, CheckInTopic $topic, CheckInAnswer $answer): CheckIn
    {
        $latest = $user->latestCheckIn()->first();

        return $latest instanceof CheckIn && $latest->created_at->greaterThan(now()->subDays(DueCheckIn::EVERY_DAYS))
            ? $latest
            : $user->checkIns()->create(['topic' => $topic, 'answer' => $answer]);
    }
}
