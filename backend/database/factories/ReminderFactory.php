<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentKind;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Reminder> */
class ReminderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'appointment_kind' => AppointmentKind::CalendarEvent,
            'appointment_id' => fake()->uuid(),
            'sent_at' => now(),
        ];
    }
}
