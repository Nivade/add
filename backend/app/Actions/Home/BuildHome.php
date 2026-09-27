<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Actions\Sessions\BuildExecutionState;
use App\Contracts\Appointment;
use App\Contracts\NextActionResolver;
use App\Data\ComingUpData;
use App\Data\ExecutionStateData;
use App\Data\HomeData;
use App\Data\JustFinishedData;
use App\Data\NextActionData;
use App\Data\ReminderData;
use App\Enums\AppointmentKind;
use App\Enums\IntentionStatus;
use App\Models\Commitment;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use App\Support\NextAction\ResolutionContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsObject;

/** Home asks one question per band and answers each with one thing, or a count. */
final class BuildHome
{
    use AsObject;

    /** Long enough to come back from a break and still see what you finished. */
    private const int JUST_FINISHED_WITHIN_MINUTES = 60;

    public function __construct(private readonly NextActionResolver $resolver) {}

    public function handle(User $user): HomeData
    {
        $context = ResolutionContext::forUser($user);
        $needsAttention = BuildNeedsAttention::run($user, $context->now);
        $rightNow = $this->resolver->resolve($user, $context);

        return new HomeData(
            rightNow: $rightNow,
            rightNowIsCommitment: $rightNow instanceof NextActionData && $this->isCommitment($rightNow->intention->id),
            session: $this->session($user),
            comingUp: $this->comingUp($context),
            reminder: $this->reminder($user, $context),
            justFinished: $this->justFinished($user, $context->now),
            needsAttention: $needsAttention['items'],
            restCount: $this->open($user)->count() + $needsAttention['outstanding'] - count($needsAttention['items']),
        );
    }

    private function isCommitment(string $intentionId): bool
    {
        return Commitment::query()->open()->where('intention_id', $intentionId)->exists();
    }

    private function justFinished(User $user, CarbonImmutable $now): ?JustFinishedData
    {
        $intention = Intention::query()
            ->where('user_id', $user->id)
            ->where('status', IntentionStatus::Done)
            ->where('completed_at', '>=', $now->subMinutes(self::JUST_FINISHED_WITHIN_MINUTES))
            ->latest('completed_at')
            ->first();

        return $intention instanceof Intention ? JustFinishedData::from($intention) : null;
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

    /** @return Builder<Intention> */
    private function open(User $user): Builder
    {
        return Intention::query()->where('user_id', $user->id)->open();
    }
}
