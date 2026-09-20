<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\AppointmentKind;
use App\Enums\PlanRung;
use Carbon\CarbonImmutable;

/** Anything a day can be planned backwards from: a dated intention, or an event off the calendar. */
interface Appointment
{
    public function appointmentKind(): AppointmentKind;

    public function appointmentId(): string;

    public function appointmentTitle(): string;

    public function appointmentAt(): ?CarbonImmutable;

    /** True while the time was read out of the person's words and nobody has confirmed it. */
    public function appointmentInferred(): bool;

    public function statedSeconds(PlanRung $rung): ?int;

    public function stateSeconds(PlanRung $rung, ?int $seconds): void;
}
