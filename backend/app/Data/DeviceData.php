<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\DevicePlatform;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A device that can receive a push. The token itself never comes back out. */
#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class DeviceData extends Data
{
    public function __construct(
        public string $id,
        public DevicePlatform $platform,
    ) {}
}
