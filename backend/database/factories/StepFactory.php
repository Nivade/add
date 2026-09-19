<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StepStatus;
use App\Models\Intention;
use App\Models\Step;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Step> */
class StepFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'intention_id' => Intention::factory(),
            'title' => 'Grab a bin bag.',
            'position' => 1,
            'estimated_seconds' => 180,
            'status' => StepStatus::Pending,
            'skip_count' => 0,
            'generated' => true,
            'completed_at' => null,
            'last_skipped_at' => null,
        ];
    }

    public function done(): self
    {
        return $this->state([
            'status' => StepStatus::Done,
            'completed_at' => now(),
        ]);
    }

    public function skipped(int $times = 1): self
    {
        return $this->state([
            'status' => StepStatus::Skipped,
            'skip_count' => $times,
            'last_skipped_at' => now(),
        ]);
    }
}
