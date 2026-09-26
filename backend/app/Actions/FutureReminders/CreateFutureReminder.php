<?php

declare(strict_types=1);

namespace App\Actions\FutureReminders;

use App\Contracts\DeadlineExtractor;
use App\Models\FutureReminder;
use App\Models\User;
use App\Support\Time\ExtractedDeadline;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * §15, time trigger only. The extractor reads the phrase deterministically — no model call,
 * the same zone-aware technique `ConvertCaptureToIntention` already uses for capture deadlines.
 */
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
            'message' => $this->unclaimed($text, $extracted),
            'trigger_at' => $extracted->at,
        ]);
    }

    private function unclaimed(string $text, ExtractedDeadline $extracted): string
    {
        $remainder = Str::squish(str_replace($extracted->phrase, ' ', $text));
        $remainder = trim($remainder, " \t\n\r\0\x0B,.-");

        return $remainder === '' ? $text : $remainder;
    }
}
