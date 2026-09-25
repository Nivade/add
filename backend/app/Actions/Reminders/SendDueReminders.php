<?php

declare(strict_types=1);

namespace App\Actions\Reminders;

use App\Concerns\QueuesPerUser;
use App\Contracts\Appointment;
use App\Data\BackwardsPlanData;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use App\Support\Time\BackwardsPlan;
use App\Support\Time\NextAppointment;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;

/** One reminder per appointment, sent when its first preparation is due and not before. */
final class SendDueReminders
{
    use AsCommand;
    use AsJob;
    use AsObject;
    use QueuesPerUser;

    public string $commandSignature = 'reminders:dispatch {user? : the id of one person, or every person when omitted}';

    public string $commandDescription = 'Send the reminders whose preparation is due.';

    /** @return list<Reminder> */
    public function handle(User $user, ?CarbonImmutable $now = null): array
    {
        $now ??= $user->now();

        // A plan only exists for an appointment today, so nothing past tonight can be due.
        $appointments = NextAppointment::upcomingForUser($user, $now, $now->endOfDay());
        $already = $this->alreadyReminded($user, $appointments);
        $sent = [];

        foreach ($appointments as $appointment) {
            $reminder = $this->remind($user, $appointment, $now, $already);

            if ($reminder instanceof Reminder) {
                $sent[] = $reminder;
            }
        }

        return $sent;
    }

    /**
     * @param  list<Appointment>  $appointments
     * @return list<string>
     */
    private function alreadyReminded(User $user, array $appointments): array
    {
        if ($appointments === []) {
            return [];
        }

        return array_values(Reminder::query()
            ->where('user_id', $user->id)
            ->whereIn('appointment_id', array_map(
                fn (Appointment $appointment): string => $appointment->appointmentId(),
                $appointments,
            ))
            ->get()
            ->map(fn (Reminder $reminder): string => $reminder->appointment_kind->value.':'.$reminder->appointment_id)
            ->all());
    }

    /** @param  list<string>  $already */
    private function remind(User $user, Appointment $appointment, CarbonImmutable $now, array $already): ?Reminder
    {
        $plan = BackwardsPlan::for($appointment, $now);

        if (! $plan instanceof BackwardsPlanData || $plan->firstRung()->instant() > $now) {
            return null;
        }

        $key = $appointment->appointmentKind()->value.':'.$appointment->appointmentId();

        if (in_array($key, $already, strict: true)) {
            return null;
        }

        $user->notify(new AppointmentReminder($appointment, $plan, $now));

        return Reminder::query()->create([
            'user_id' => $user->id,
            'appointment_kind' => $appointment->appointmentKind(),
            'appointment_id' => $appointment->appointmentId(),
            'sent_at' => $now,
        ]);
    }
}
