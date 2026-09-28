<?php

declare(strict_types=1);

namespace App\Actions\FutureReminders;

use App\Actions\Concerns\QueuesPerUser;
use App\Attributes\PerUserCommand;
use App\Enums\Cadence;
use App\Models\FutureReminder;
use App\Models\User;
use App\Notifications\FutureReminderDue;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;

/** No plan and no preparation, only an instant, so it is not a branch of `SendDueReminders`. */
#[PerUserCommand(
    name: 'future-reminders:dispatch',
    description: 'Send the future-self reminders whose trigger has passed.',
    every: Cadence::EveryMinute,
)]
final class SendDueFutureReminders
{
    use AsCommand;
    use AsJob;
    use AsObject;
    use QueuesPerUser;

    /** @return list<FutureReminder> */
    public function handle(User $user, ?CarbonImmutable $now = null): array
    {
        $now ??= $user->now();

        $due = FutureReminder::query()->where('user_id', $user->id)->due($now)->get();

        foreach ($due as $reminder) {
            $user->notify(new FutureReminderDue($reminder));
        }

        FutureReminder::query()->whereKey($due->modelKeys())->update(['sent_at' => $now]);

        return array_values($due->all());
    }

    /**
     * Only someone with a reminder already due is worth a job.
     *
     * @param  Builder<User>  $query
     */
    protected function constrainQueued(Builder $query): void
    {
        $query->whereIn('id', FutureReminder::query()->select('user_id')->due(CarbonImmutable::now()));
    }
}
