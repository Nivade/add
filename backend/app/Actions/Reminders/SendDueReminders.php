<?php

declare(strict_types=1);

namespace App\Actions\Reminders;

use App\Contracts\Appointment;
use App\Data\BackwardsPlanData;
use App\Enums\IntentionStatus;
use App\Models\CalendarEvent;
use App\Models\Intention;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use App\Support\NextAction\ResolutionContext;
use App\Support\Time\BackwardsPlan;
use App\Support\Time\ReminderLines;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;

/** One reminder per appointment, sent when its first preparation is due and not before. */
final class SendDueReminders
{
    use AsCommand;
    use AsObject;

    public string $commandSignature = 'reminders:dispatch {user? : the id of one person, or every person when omitted}';

    public string $commandDescription = 'Send the reminders whose preparation is due.';

    /** @return list<Reminder> */
    public function handle(User $user, ?CarbonImmutable $now = null): array
    {
        $now ??= ResolutionContext::forUser($user)->now;
        $sent = [];

        foreach ($this->appointments($user, $now) as $appointment) {
            $reminder = $this->remind($user, $appointment, $now);

            if ($reminder instanceof Reminder) {
                $sent[] = $reminder;
            }
        }

        return $sent;
    }

    public function asCommand(Command $command): void
    {
        $users = User::query()
            ->when($command->argument('user'), fn ($query, $id) => $query->whereKey($id))
            ->get();

        foreach ($users as $user) {
            $command->info($user->email.': '.count($this->handle($user)).' reminders.');
        }
    }

    /** @return list<Appointment> */
    private function appointments(User $user, CarbonImmutable $now): array
    {
        $intentions = Intention::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [IntentionStatus::Captured, IntentionStatus::Active])
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '>=', $now)
            ->get()
            ->all();

        $events = CalendarEvent::query()
            ->where('user_id', $user->id)
            ->where('starts_at', '>=', $now)
            ->get()
            ->all();

        return [...$intentions, ...$events];
    }

    private function remind(User $user, Appointment $appointment, CarbonImmutable $now): ?Reminder
    {
        $plan = BackwardsPlan::for($appointment, $now);

        if (! $plan instanceof BackwardsPlanData || CarbonImmutable::parse($plan->rungs[0]->at) > $now) {
            return null;
        }

        $already = Reminder::query()
            ->where('user_id', $user->id)
            ->where('appointment_kind', $appointment->appointmentKind())
            ->where('appointment_id', $appointment->appointmentId())
            ->exists();

        if ($already) {
            return null;
        }

        $user->notify(new AppointmentReminder($appointment, ReminderLines::for($appointment, $plan, $now)));

        return Reminder::query()->create([
            'user_id' => $user->id,
            'appointment_kind' => $appointment->appointmentKind(),
            'appointment_id' => $appointment->appointmentId(),
            'sent_at' => $now->utc(),
        ]);
    }
}
