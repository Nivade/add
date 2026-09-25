<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Actions\Sessions\BuildExecutionState;
use App\Contracts\Appointment;
use App\Contracts\NextActionResolver;
use App\Data\ComingUpData;
use App\Data\HomeData;
use App\Data\NeedsAttentionData;
use App\Data\ReminderData;
use App\Enums\AppointmentKind;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\User;
use App\Models\WaitingFor;
use App\Notifications\AppointmentReminder;
use App\Support\NextAction\ResolutionContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsObject;

/** Home asks one question per band and answers each with one thing, or a count. */
final class BuildHome
{
    use AsObject;

    /** Enough to act on, few enough that the band is not a list to work through. */
    private const int NEEDS_ATTENTION_LIMIT = 3;

    /** How long a waiting-for goes untouched before it is worth a nudge. */
    private const int WAITING_FOR_STALE_AFTER_DAYS = 4;

    public function __construct(private readonly NextActionResolver $resolver) {}

    public function handle(User $user): HomeData
    {
        $context = ResolutionContext::forUser($user);
        $session = $user->runningSession()->getResults();

        $clarifications = $this->open($user)
            ->awaitingClarification()
            ->oldest()
            ->limit(self::NEEDS_ATTENTION_LIMIT)
            ->get();

        $staleWaitingFor = $this->staleWaitingFor($user, $context->now);

        $needsAttention = $clarifications
            ->map(fn (Intention $intention): NeedsAttentionData => NeedsAttentionData::forIntention($intention))
            ->all();

        if ($staleWaitingFor instanceof WaitingFor) {
            $needsAttention[] = NeedsAttentionData::forWaitingFor($staleWaitingFor);
        }

        $openWaitingFors = WaitingFor::query()->where('user_id', $user->id)->open()->count();

        return new HomeData(
            rightNow: $this->resolver->resolve($user, $context),
            session: $session instanceof ExecutionSession ? BuildExecutionState::run($session) : null,
            comingUp: $this->comingUp($context),
            reminder: $this->reminder($user, $context),
            needsAttention: array_values($needsAttention),
            restCount: $this->open($user)->count() - $clarifications->count()
                + $openWaitingFors - ($staleWaitingFor instanceof WaitingFor ? 1 : 0),
        );
    }

    /** At most one, the same one-thing-at-a-time rule the clarifying-question slot already follows. */
    private function staleWaitingFor(User $user, CarbonImmutable $now): ?WaitingFor
    {
        return WaitingFor::query()
            ->where('user_id', $user->id)
            ->stale($now, self::WAITING_FOR_STALE_AFTER_DAYS)
            ->oldest('updated_at')
            ->first();
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
