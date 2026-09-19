<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CalendarEvent> */
class CalendarEventFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source' => 'fixture',
            'external_id' => fake()->uuid(),
            'title' => 'Dentist',
            'location' => null,
            'starts_at' => now()->addHours(5),
            'ends_at' => null,
            'travel_seconds' => null,
            'preparation_seconds' => null,
            'gathering_seconds' => null,
        ];
    }
}
