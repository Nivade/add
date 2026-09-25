<?php

declare(strict_types=1);

namespace App\Actions\Intentions;

use App\Enums\IntentionStatus;
use App\Models\Intention;
use LogicException;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * A fresh intention from a due template, re-decomposed rather than copied: a stale step set is
 * worse than a fresh decomposition pass, so no steps travel with it.
 */
final class CreateRecurringIntention
{
    use AsObject;

    public function handle(Intention $template): Intention
    {
        $everyDays = $template->recurrence_every_days ?? throw new LogicException("Intention {$template->id} has no recurrence.");
        $nextAt = $template->recurrence_next_at ?? throw new LogicException("Intention {$template->id} has no recurrence.");

        $fresh = Intention::query()->create([
            'user_id' => $template->user_id,
            'title' => $template->title,
            'why' => $template->why,
            'status' => IntentionStatus::Captured,
        ]);

        DecomposeIntention::dispatch($fresh);

        $template->update([
            'recurrence_next_at' => $nextAt->addDays($everyDays),
        ]);

        return $fresh;
    }
}
