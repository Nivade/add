<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DevicePlatform;
use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Device> */
class DeviceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'push_token' => 'ExponentPushToken['.fake()->unique()->lexify('??????????????????????').']',
            'platform' => DevicePlatform::Ios,
        ];
    }
}
