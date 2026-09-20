<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Appointment;
use App\Models\CalendarEvent;
use App\Models\Intention;
use Illuminate\Database\Eloquent\Model;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Which surface an appointment came from, so the screen can say whose word it is. */
#[TypeScript]
enum AppointmentKind: string
{
    case Intention = 'intention';
    case CalendarEvent = 'calendar_event';

    /** @return class-string<Appointment&Model> */
    public function model(): string
    {
        return match ($this) {
            self::Intention => Intention::class,
            self::CalendarEvent => CalendarEvent::class,
        };
    }

    public function find(string $id): ?Appointment
    {
        return $this->model()::query()->find($id);
    }
}
