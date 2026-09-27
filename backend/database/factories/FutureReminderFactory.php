<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FutureReminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FutureReminder> */
class FutureReminderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'message' => 'Buy dishwasher tablets',
            'trigger_at' => now()->addDay(),
            'calendar_event_id' => null,
            'offset_seconds' => null,
            'sent_at' => null,
        ];
    }
}
