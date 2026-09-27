<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommitmentProvenance;
use App\Enums\CommitmentStatus;
use App\Models\Commitment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Commitment> */
class CommitmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => "I'll call Sarah Friday",
            'provenance' => CommitmentProvenance::UserStated,
            'confirmed_at' => now(),
            'status' => CommitmentStatus::Open,
        ];
    }

    public function inferred(): self
    {
        return $this->state(fn (): array => [
            'provenance' => CommitmentProvenance::SystemInferred,
            'confirmed_at' => null,
        ]);
    }
}
