<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Data\SortedCaptureData;
use App\Enums\CaptureKind;
use App\Models\Capture;
use App\Models\FutureReminder;
use App\Models\User;
use App\Models\WaitingFor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/** A thought needs no read-back: it shows up as its first step. Every other sort waits for an answer. */
final class BuildSortedCaptures
{
    use AsObject;

    private const int SHOWN = 3;

    /** @return array{items: list<SortedCaptureData>, more: int} */
    public function handle(User $user): array
    {
        $total = Capture::query()->where('user_id', $user->id)->awaitingReadBack()->count();
        $captures = Capture::query()->where('user_id', $user->id)->awaitingReadBack()->latest()->limit(self::SHOWN)->get();

        $subjects = WaitingFor::query()->whereKey($this->routedIds($captures, CaptureKind::WaitingFor))->pluck('subject', 'id');
        $triggers = FutureReminder::query()->whereKey($this->routedIds($captures, CaptureKind::Reminder))->get()->keyBy('id');
        $now = $user->now();

        $items = $captures->map(fn (Capture $capture): SortedCaptureData => new SortedCaptureData(
            id: $capture->id,
            excerpt: $capture->excerpt(),
            kind: $capture->kind ?? CaptureKind::Thought,
            detail: match ($capture->kind) {
                CaptureKind::WaitingFor => $subjects->get((string) $capture->routed_id),
                CaptureKind::Reminder => $this->inWords($triggers->get((string) $capture->routed_id), $now),
                default => null,
            },
        ));

        return ['items' => array_values($items->all()), 'more' => max(0, $total - self::SHOWN)];
    }

    /**
     * @param  Collection<int, Capture>  $captures
     * @return list<string>
     */
    private function routedIds(Collection $captures, CaptureKind $kind): array
    {
        return array_values($captures->where('kind', $kind)->pluck('routed_id')->filter()->all());
    }

    private function inWords(?FutureReminder $reminder, CarbonImmutable $now): ?string
    {
        return $reminder?->trigger_at->setTimezone($now->getTimezone())->calendar($now, [
            'sameDay' => '[today at] HH:mm',
            'nextDay' => '[tomorrow at] HH:mm',
            'nextWeek' => 'dddd [at] HH:mm',
            'sameElse' => 'D MMMM [at] HH:mm',
        ]);
    }
}
