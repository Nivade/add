<?php

declare(strict_types=1);

namespace App\Actions\Intentions;

use App\Enums\IntentionStatus;
use App\Exceptions\InvalidIntentionTransition;
use App\Models\Intention;
use Lorisleiva\Actions\Concerns\AsObject;

/** "Repeat this": only offered from an intention the person is looking at because it is finished. */
final class SetIntentionRecurrence
{
    use AsObject;

    public function handle(Intention $intention, int $everyDays): Intention
    {
        if ($intention->status !== IntentionStatus::Done) {
            throw new InvalidIntentionTransition("Intention {$intention->id} is not done.");
        }

        if ($intention->recurrence_template_id !== null) {
            throw new InvalidIntentionTransition("Intention {$intention->id} already repeats through its template.");
        }

        $intention->update([
            'recurrence_every_days' => $everyDays,
            'recurrence_next_at' => ($intention->completed_at ?? $intention->user->now())->addDays($everyDays),
        ]);

        return $intention;
    }
}
