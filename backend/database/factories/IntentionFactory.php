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
            'clarifying_question' => null,
            'clarification' => null,
            'deadline_at' => null,
            'decomposed_at' => null,
            'completed_at' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(['status' => IntentionStatus::Active]);
    }

    public function unclear(string $question = 'Which thing do you mean?'): self
    {
        return $this->state(['clarifying_question' => $question]);
    }

    public function decomposed(): self
    {
        return $this->state([
            'status' => IntentionStatus::Active,
            'decomposed_at' => now(),
        ]);
    }
}
