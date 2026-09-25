<?php

declare(strict_types=1);

namespace App\Actions\Intentions;

use App\Actions\Concerns\QueuesPerUser;
use App\Models\Intention;
use App\Models\User;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;

/** One person per job, same shape as `SendDueFutureReminders`. */
final class SendDueRecurringIntentions
{
    use AsCommand;
    use AsJob;
    use AsObject;
    use QueuesPerUser;

    public string $commandSignature = 'intentions:recur {user? : the id of one person, or every person when omitted}';

    public string $commandDescription = 'Create a fresh intention from every recurring intention whose template is due.';

    /** @return list<Intention> */
    public function handle(User $user, ?CarbonImmutable $now = null): array
    {
        $now ??= $user->now();

        $due = Intention::query()
            ->where('user_id', $user->id)
            ->dueForRecurrence($now)
            ->get();

        $created = [];

        foreach ($due as $template) {
            $created[] = CreateRecurringIntention::run($template);
        }

        return $created;
    }
}
