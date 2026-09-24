<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Contracts\Appointment;
use App\Contracts\NextActionResolver;
use App\Data\ComingUpData;
use App\Data\ExecutionStateData;
use App\Data\HomeData;
use App\Data\IntentionData;
use App\Data\ReminderData;
use App\Enums\AppointmentKind;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use App\Support\Execution\RunningSession;
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

    public function __construct(private readonly NextActionResolver $resolver) {}

    public function handle(User $user): HomeData
    {
        $context = ResolutionContext::forUser($user);
        $session = RunningSession::forUser($user);

        $needsAttention = $this->open($user)
            ->awaitingClarification()
            ->oldest()
            ->limit(self::NEEDS_ATTENTION_LIMIT)
            ->get();

        return new HomeData(
            rightNow: $this->resolver->resolve($user, $context),
            session: $session instanceof ExecutionSession ? ExecutionStateData::of($session) : null,
            comingUp: $this->comingUp($context),
            reminder: $this->reminder($user, $context),
            needsAttention: array_values($needsAttention
                ->map(fn (Intention $intention): IntentionData => IntentionData::from($intention))
                ->all()),
            restCount: $this->open($user)->count() - $needsAttention->count(),
        );
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
