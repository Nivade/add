<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Place;
use App\Models\NotHereReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotHereReport> */
class NotHereReportFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'place' => Place::Home,
        ];
    }
}
