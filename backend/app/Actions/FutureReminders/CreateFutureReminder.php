<?php

declare(strict_types=1);

namespace App\Actions\FutureReminders;

use App\Contracts\DeadlineExtractor;
use App\Models\FutureReminder;
use App\Models\User;
use App\Support\Time\ExtractedDeadline;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsObject;

/** §15, time trigger only: the phrase is read deterministically, never by a model. */
final class CreateFutureReminder
{
    use AsObject;

    public function __construct(
        private readonly DeadlineExtractor $extractor,
    ) {}

    public function handle(User $user, string $text): FutureReminder
    {
        $now = $user->now();
        $extracted = $this->extractor->extract($text, $now);

        if (! $extracted instanceof ExtractedDeadline) {
            throw ValidationException::withMessages([
                'text' => 'Couldn\'t find a time in that — try something like "tomorrow at 5".',
            ]);
        }

        return FutureReminder::query()->create([
            'user_id' => $user->id,
            'message' => $extracted->remainderOf($text, ',.-'),
            'trigger_at' => $extracted->at,
        ]);
    }
}
