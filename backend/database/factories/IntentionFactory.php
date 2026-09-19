<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\IntentionStatus;
use App\Models\Intention;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Intention> */
class IntentionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => 'Clean the apartment',
            'why' => null,
            'status' => IntentionStatus::Captured,
            'deadline_at' => null,
            'decomposed_at' => null,
            'completed_at' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(['status' => IntentionStatus::Active]);
    }

    public function decomposed(): self
    {
        return $this->state([
            'status' => IntentionStatus::Active,
            'decomposed_at' => now(),
        ]);
    }
}
