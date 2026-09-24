<?php

declare(strict_types=1);

namespace App\Actions\Intentions;

use App\Models\Intention;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsObject;

/** The answer lands on the intention it was asked about, so the capture it came from stays its origin. */
final class ClarifyIntention
{
    use AsObject;

    public function handle(Intention $intention, string $answer): Intention
    {
        $answered = Intention::query()
            ->whereKey($intention->id)
            ->awaitingClarification()
            ->update(['clarification' => $answer]);

        if ($answered === 0) {
            throw ValidationException::withMessages(['answer' => __('This has already been answered.')]);
        }

        $intention->refresh();

        DecomposeIntention::dispatch($intention);

        return $intention;
    }
}
