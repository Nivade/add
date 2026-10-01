<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Actions\CheckIns\DueCheckIn;
use App\Actions\Sessions\BuildExecutionState;
use App\Contracts\Appointment;
use App\Contracts\NextActionResolver;
use App\Data\ComingUpData;
use App\Data\ExecutionStateData;
use App\Data\HomeData;
use App\Data\JustFinishedData;
use App\Data\NeedsAttentionData;
use App\Data\NextActionData;
use App\Data\ReminderData;
use App\Enums\AppointmentKind;
use App\Enums\CaptureKind;
use App\Enums\IntentionStatus;
use App\Enums\NeedsAttentionKind;
use App\Models\Capture;
use App\Models\Commitment;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use App\Support\NextAction\ResolutionContext;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsObject;

/** Home asks one question per band and answers each with one thing, or a count. */
final class BuildHome
{
    use AsObject;

    /** Long enough to come back from a break and still see what you finished. */
    private const int JUST_FINISHED_WITHIN_MINUTES = 60;

    /** Past a day, an unsorted capture is stuck rather than being sorted. */
    private const int SORTING_WITHIN_HOURS = 24;

    public function __construct(
        private readonly NextActionResolver $resolver,
        private readonly CountOpenThings $countOpenThings,
    ) {}

    public function handle(User $user, ?ResolutionContext $context = null): HomeData
    {
        $context ??= ResolutionContext::forUser($user);
        $openCommitments = Commitment::query()->where('user_id', $user->id)->open()->mostPressingFirst()->get();
        $readingBack = $this->promisesReadingBack($user);
        $needsAttention = BuildNeedsAttention::run($user, $context->now, $openCommitments->first(
            fn (Commitment $commitment): bool => $commitment->isStandalone() && ! in_array($commitment->id, $readingBack, true),
        ));
        $sorted = BuildSortedCaptures::run($user);
        $rightNow = $this->resolver->resolve($user, $context);
        $session = $this->session($user);
        $reminder = $this->reminder($user, $context);

        return new HomeData(
            rightNow: $rightNow,
            rightNowIsCommitment: $rightNow instanceof NextActionData && $openCommitments->contains('intention_id', $rightNow->intention->id),
            session: $session,
            comingUp: $this->comingUp($context),
            reminder: $reminder,
            justFinished: $this->justFinished($user, $context->now),
            needsAttention: $needsAttention,
            restCount: $this->restCount($user, $needsAttention, $session?->intention->id ?? $rightNow?->intention->id),
            sortingCount: $this->sortingCount($user, $context->now),
            sorted: $sorted['items'],
            sortedMore: $sorted['more'],
            unsortedCount: $this->unsortedCount($user),
            aiConsented: $user->hasConsentedToAi(),
            hasOpenCommitments: $openCommitments->isNotEmpty(),
            checkIn: ! $session instanceof ExecutionStateData && ! $reminder instanceof ReminderData ? DueCheckIn::run($user, $context->now) : null,
        );
    }

    /** @param  list<NeedsAttentionData>  $needsAttention */
    private function restCount(User $user, array $needsAttention, ?string $shownIntentionId): int
    {
        $shownElsewhere = array_any(
            $needsAttention,
            fn (NeedsAttentionData $item): bool => $item->kind === NeedsAttentionKind::Intention && $item->id === $shownIntentionId,
        );
        $shownAbove = $shownIntentionId !== null && ! $shownElsewhere ? 1 : 0;

        return max(0, $this->countOpenThings->handle($user) - count($needsAttention) - $shownAbove);
    }

    /**
     * An inferred promise still being read back is asked about there, not twice.
     *
     * @return list<string>
     */
    private function promisesReadingBack(User $user): array
    {
        return array_values(Capture::query()
            ->where('user_id', $user->id)
            ->awaitingReadBack()
            ->where('kind', CaptureKind::Promise)
            ->pluck('routed_id')
            ->all());
    }

    private function unsortedCount(User $user): int
    {
        return Capture::query()->where('user_id', $user->id)->failedToSort()->count();
    }

    private function sortingCount(User $user, CarbonImmutable $now): int
    {
        return Capture::query()
            ->where('user_id', $user->id)
            ->whereNull('processed_at')
            ->whereNull('failed_at')
            ->where('created_at', '>=', $now->subHours(self::SORTING_WITHIN_HOURS))
            ->count();
    }

    private function justFinished(User $user, CarbonImmutable $now): ?JustFinishedData
    {
        $intention = Intention::query()
            ->where('user_id', $user->id)
            ->where('status', IntentionStatus::Done)
            ->where('completed_at', '>=', $now->subMinutes(self::JUST_FINISHED_WITHIN_MINUTES))
            ->latest('completed_at')
            ->with('recurrenceTemplate')
            ->first();

        return $intention instanceof Intention ? new JustFinishedData($intention->id, $intention->title, $intention->repeatsEveryDays()) : null;
    }

    private function session(User $user): ?ExecutionStateData
    {
        $session = $user->runningSession()->getResults();

        return $session instanceof ExecutionSession ? BuildExecutionState::run($session) : null;
    }

    /** The band stands until the person dismisses it or the appointment it prepared for is behind them. */
    private function reminder(User $user, ResolutionContext $context): ?ReminderData
    {
        $notification = $user->unreadNotifications()
            ->where('type', AppointmentReminder::class)
            ->latest()
            ->first();

        if ($notification === null) {
            return null;
        }

        $data = $notification->data;
        $lines = $data['lines'] ?? [];

        if (! $this->stillAhead($data, $context)) {
            $notification->markAsRead();

            return null;
        }

        return new ReminderData(
            $notification->id,
            is_string($data['title'] ?? null) ? $data['title'] : '',
            is_array($lines) ? array_values(array_filter($lines, is_string(...))) : [],
        );
    }

    /** @param  array<string, mixed>  $data */
    private function stillAhead(array $data, ResolutionContext $context): bool
    {
        $kind = AppointmentKind::tryFrom(is_string($data['kind'] ?? null) ? $data['kind'] : '');
        $id = is_string($data['appointment_id'] ?? null) ? $data['appointment_id'] : '';

        $appointment = $kind?->find($id);
        $at = $appointment?->appointmentAt();

        return $at instanceof CarbonImmutable && $at > $context->now;
    }

    private function comingUp(ResolutionContext $context): ?ComingUpData
    {
        return $context->appointment instanceof Appointment
            ? ComingUpData::of($context->appointment, $context->now)
            : null;
    }
}
