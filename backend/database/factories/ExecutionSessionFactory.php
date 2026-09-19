<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SessionOutcome;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExecutionSession> */
class ExecutionSessionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'intention_id' => Intention::factory(),
            'current_step_id' => null,
            'outcome' => null,
            'steps_completed' => 0,
            'started_at' => now(),
            'paused_at' => null,
            'ended_at' => null,
        ];
    }

    public function stopped(): self
    {
        return $this->state([
            'outcome' => SessionOutcome::Stopped,
            'ended_at' => now(),
        ]);
    }
}
