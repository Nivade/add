<?php

declare(strict_types=1);

namespace App\Actions\Intentions;

use App\Enums\IntentionStatus;
use App\Models\Intention;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;
use Lorisleiva\Actions\Concerns\AsObject;

/** Re-decomposed rather than copied: a stale step set is worse than a fresh pass. */
final class CreateRecurringIntention
{
    use AsObject;

    public function handle(Intention $template, CarbonImmutable $now): Intention
    {
        $everyDays = $template->recurrence_every_days ?? throw new LogicException("Intention {$template->id} has no recurrence.");
        $nextAt = $template->recurrence_next_at ?? throw new LogicException("Intention {$template->id} has no recurrence.");

        $fresh = DB::transaction(function () use ($template, $everyDays, $nextAt, $now): Intention {
            $template->update(['recurrence_next_at' => $this->nextAfter($nextAt, $everyDays, $now)]);

            return Intention::query()->create([
                'user_id' => $template->user_id,
                'recurrence_template_id' => $template->id,
                'title' => $template->title,
                'why' => $template->why,
                'status' => IntentionStatus::Captured,
            ]);
        });

        DecomposeIntention::dispatch($fresh);

        return $fresh;
    }

    /** Missed runs collapse into one fresh intention; the schedule keeps its original rhythm. */
    private function nextAfter(CarbonImmutable $nextAt, int $everyDays, CarbonImmutable $now): CarbonImmutable
    {
        $missed = intdiv((int) $nextAt->diffInDays($now), $everyDays) + 1;

        return $nextAt->addDays($missed * $everyDays);
    }
}
