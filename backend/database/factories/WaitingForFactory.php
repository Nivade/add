<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WaitingForStatus;
use App\Models\User;
use App\Models\WaitingFor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WaitingFor> */
class WaitingForFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => 'John',
            'note' => 'the contract',
            'status' => WaitingForStatus::Waiting,
        ];
    }

    public function stale(int $days = 4): self
    {
        return $this->state(fn (): array => [
            'created_at' => now()->subDays($days),
            'updated_at' => now()->subDays($days),
        ]);
    }
}
