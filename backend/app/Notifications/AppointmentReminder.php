<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\Appointment;
use App\Contracts\ExpoPushable;
use App\Data\BackwardsPlanData;
use App\Notifications\Channels\ExpoPushChannel;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\Notification;

/** Carries the preparation and the leave-by time. A bare "dentist tomorrow" is a bug. */
final class AppointmentReminder extends Notification implements ExpoPushable
{
    /** @var list<string> */
    private readonly array $lines;

    public function __construct(
        private readonly Appointment $appointment,
        BackwardsPlanData $plan,
        CarbonImmutable $now,
    ) {
        $this->lines = $this->buildLines($plan, $now);
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', ExpoPushChannel::class];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->appointment->appointmentKind()->value,
            'appointment_id' => $this->appointment->appointmentId(),
            'title' => $this->appointment->appointmentTitle(),
            'lines' => $this->lines,
        ];
    }

    /**
     * The same lines the band shows — truncating for a lock screen is the platform's call, not ours.
     *
     * @return array{title: string, body: string, data: array<string, mixed>}
     */
    public function toExpo(object $notifiable): array
    {
        return [
            'title' => $this->appointment->appointmentTitle(),
            'body' => implode("\n", $this->lines),
            'data' => [
                'kind' => $this->appointment->appointmentKind()->value,
                'appointment_id' => $this->appointment->appointmentId(),
            ],
        ];
    }

    /**
     * §23: a notification answers why it interrupted, what to do, why now, and what happens if it waits.
     *
     * @return list<string>
     */
    private function buildLines(BackwardsPlanData $plan, CarbonImmutable $now): array
    {
        $first = $plan->firstRung();
        $leave = $plan->leaveRung();
        $minutesToLeave = (int) round($now->diffInMinutes($leave->instant(), absolute: false));

        return [
            $this->appointment->appointmentTitle().' is at '.$plan->deadlineClock.'.',
            $first->rung->startingWords(),
            $minutesToLeave > 0
                ? 'Leaving is '.$minutesToLeave.' minutes away.'
                : 'Leaving is what comes next.',
            'If this waits, leaving at '.$leave->clock.' waits with it.',
        ];
    }
}
