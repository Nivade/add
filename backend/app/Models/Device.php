<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DevicePlatform;
use App\Models\Concerns\StoresDatesInUtc;
use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
#[UseFactory(DeviceFactory::class)]
class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    use HasUlids;
    use StoresDatesInUtc;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'platform' => DevicePlatform::class,
        ];
    }
}
