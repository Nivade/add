<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\Appointment;
use App\Contracts\ExpoPushable;
use App\Notifications\Channels\ExpoPushChannel;
use Illuminate\Notifications\Notification;

/** Carries the preparation and the leave-by time. A bare "dentist tomorrow" is a bug. */
final class AppointmentReminder extends Notification implements ExpoPushable
{
    /** @param  list<string>  $lines */
    public function __construct(
        private readonly Appointment $appointment,
        private readonly array $lines,
    ) {}

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
}
