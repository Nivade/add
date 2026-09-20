<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\Appointment;
use Illuminate\Notifications\Notification;

/** Carries the preparation and the leave-by time. A bare "dentist tomorrow" is a bug. */
final class AppointmentReminder extends Notification
{
    /** @param  list<string>  $lines */
    public function __construct(
        private readonly Appointment $appointment,
        private readonly array $lines,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
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
}
