<?php

declare(strict_types=1);

namespace App\Actions\Intentions;

use App\Models\Intention;
use Lorisleiva\Actions\Concerns\AsObject;

/** A deadline read out of a sentence becomes fact only when the person says it is one. */
final class ConfirmDeadline
{
    use AsObject;

    public function handle(Intention $intention): Intention
    {
        $intention->update(['deadline_confirmed_at' => now()]);

        return $intention;
    }
}
