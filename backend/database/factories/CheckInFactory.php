<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CheckInAnswer;
use App\Enums\CheckInTopic;
use App\Models\CheckIn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CheckIn> */
class CheckInFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'topic' => CheckInTopic::Overwhelm,
            'answer' => CheckInAnswer::Less,
        ];
    }
}
