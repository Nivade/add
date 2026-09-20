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
use App\Enums\IntentionStatus;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use App\Support\NextAction\ResolutionContext;
use App\Support\Time\NextAppointment;
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
        $session = ExecutionSession::query()->where('user_id', $user->id)->running()->first();

        $needsAttention = $this->open($user)
            ->where('needs_clarification', true)
            ->oldest()
            ->limit(self::NEEDS_ATTENTION_LIMIT)
            ->get();

        return new HomeData(
            rightNow: $this->resolver->resolve($user, $context),
            session: $session instanceof ExecutionSession ? ExecutionStateData::of($session) : null,
            comingUp: $this->comingUp($user, $context),
            reminder: $this->reminder($user),
            needsAttention: array_values($needsAttention
                ->map(fn (Intention $intention): IntentionData => IntentionData::from($intention))
                ->all()),
            restCount: $this->open($user)->count() - $needsAttention->count(),
        );
    }

    /** Reading it here is what makes it read: the web has no notification tray to leave it sitting in. */
    private function reminder(User $user): ?ReminderData
    {
        $notification = $user->unreadNotifications()
            ->where('type', AppointmentReminder::class)
            ->latest()
            ->first();

        if ($notification === null) {
            return null;
        }

        $notification->markAsRead();

        $data = $notification->data;
        $lines = $data['lines'] ?? [];

        return new ReminderData(
            is_string($data['title'] ?? null) ? $data['title'] : '',
            is_array($lines) ? array_values(array_filter($lines, is_string(...))) : [],
        );
    }

    private function comingUp(User $user, ResolutionContext $context): ?ComingUpData
    {
        $next = NextAppointment::forUser($user, $context->now);

        return $next instanceof Appointment ? ComingUpData::of($next, $context->now) : null;
    }

    /** @return Builder<Intention> */
    private function open(User $user): Builder
    {
        return Intention::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [IntentionStatus::Captured, IntentionStatus::Active]);
    }
}
