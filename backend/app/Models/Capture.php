<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\Ai\ParsedCaptureData;
use App\Enums\CaptureKind;
use App\Enums\CaptureSource;
use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Database\Factories\CaptureFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string $body
 * @property CaptureSource $source
 * @property ?string $intention_id
 * @property ?CaptureKind $kind
 * @property ?string $routed_id
 * @property ?ParsedCaptureData $parsed
 * @property ?CarbonImmutable $processed_at
 * @property ?CarbonImmutable $kind_confirmed_at
 * @property ?CarbonImmutable $failed_at
 * @property CarbonImmutable $created_at
 */
#[Unguarded]
#[UseFactory(CaptureFactory::class)]
class Capture extends Model
{
    /** @use HasFactory<CaptureFactory> */
    use HasFactory;

    use HasUlids;
    use StoresDatesInUtc;

    public const ?string UPDATED_AT = null;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Intention, $this> */
    public function intention(): BelongsTo
    {
        return $this->belongsTo(Intention::class);
    }

    protected function casts(): array
    {
        return [
            'source' => CaptureSource::class,
            'kind' => CaptureKind::class,
            'parsed' => ParsedCaptureData::class,
            'processed_at' => 'immutable_datetime',
            'kind_confirmed_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
