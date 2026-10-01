<?php

declare(strict_types=1);

namespace App\Actions\Intentions;

use App\Models\Intention;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsObject;

/** The person's own answer to a deadline read out of what they wrote: a time they set is fact, and none clears it. */
final class CorrectDeadline
{
    use AsObject;

    public function handle(Intention $intention, ?CarbonImmutable $deadlineAt): Intention
    {
        $intention->update($deadlineAt instanceof CarbonImmutable
            ? ['deadline_at' => $deadlineAt, 'deadline_confirmed_at' => now()]
            : ['deadline_at' => null]);

        return $intention;
    }
}
