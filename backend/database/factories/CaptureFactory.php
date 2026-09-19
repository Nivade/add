<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CaptureSource;
use App\Models\Capture;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Capture> */
class CaptureFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
            'source' => CaptureSource::Text,
            'intention_id' => null,
            'processed_at' => null,
        ];
    }
}
