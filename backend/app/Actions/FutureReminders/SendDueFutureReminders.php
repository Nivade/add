<?php

declare(strict_types=1);

namespace App\Actions\FutureReminders;

use App\Actions\Concerns\QueuesPerUser;
use App\Models\FutureReminder;
use App\Models\User;
use App\Notifications\FutureReminderDue;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * A different shape than `SendDueReminders`: no plan, no preparation, just an instant to
 * compare against. Kept as its own command rather than a branch in that one.
 */
final class SendDueFutureReminders
{
    use AsCommand;
    use AsJob;
    use AsObject;
    use QueuesPerUser;

    public string $commandSignature = 'future-reminders:dispatch {user? : the id of one person, or every person when omitted}';

    public string $commandDescription = 'Send the future-self reminders whose trigger has passed.';

    /** @return list<FutureReminder> */
    public function handle(User $user, ?CarbonImmutable $now = null): array
    {
        $now ??= $user->now();
        $sent = [];

        $reminders = FutureReminder::query()
            ->where('user_id', $user->id)
            ->unsent()
            ->with('calendarEvent')
            ->get();

        foreach ($reminders as $reminder) {
            $firesAt = $reminder->firesAt();

            if (! $firesAt instanceof CarbonImmutable || $firesAt > $now) {
                continue;
            }

            $user->notify(new FutureReminderDue($reminder));
            $reminder->update(['sent_at' => $now]);
            $sent[] = $reminder;
        }

        return $sent;
    }
}
