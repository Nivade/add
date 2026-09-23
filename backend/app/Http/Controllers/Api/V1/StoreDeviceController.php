<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Devices\RegisterDevice;
use App\Data\DeviceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceRequest;

final class StoreDeviceController extends Controller
{
    public function __invoke(StoreDeviceRequest $request): DeviceData
    {
        return DeviceData::from(RegisterDevice::run(
            $this->user($request),
            $request->string('push_token')->toString(),
            $request->platform(),
        ));
    }
}
