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
        $recent = $user->checkIns()
            ->where('created_at', '>', now()->toImmutable()->subDays(DueCheckIn::EVERY_DAYS))
            ->latest('created_at')
            ->latest('id')
            ->first();

        return $recent ?? $user->checkIns()->create(['topic' => $topic, 'answer' => $answer]);
    }
}
