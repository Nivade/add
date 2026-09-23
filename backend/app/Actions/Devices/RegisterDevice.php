<?php

declare(strict_types=1);

namespace App\Actions\Devices;

use App\Enums\DevicePlatform;
use App\Models\Device;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsObject;

/** Expo reissues the same token to a reinstalled app, so registering twice moves the device rather than duplicating it. */
final class RegisterDevice
{
    use AsObject;

    public function handle(User $user, string $pushToken, DevicePlatform $platform): Device
    {
        return Device::query()->updateOrCreate(
            ['push_token' => $pushToken],
            ['user_id' => $user->id, 'platform' => $platform],
        );
    }
}
