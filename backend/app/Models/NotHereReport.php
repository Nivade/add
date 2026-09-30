<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Place;
use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Database\Factories\NotHereReportFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property Place $place
 * @property CarbonImmutable $created_at
 * @property-read User $user
 */
#[Unguarded]
#[UseFactory(NotHereReportFactory::class)]
class NotHereReport extends Model
{
    /** @use HasFactory<NotHereReportFactory> */
    use HasFactory;

    use HasUlids;
    use MassPrunable;
    use StoresDatesInUtc;

    public const ?string UPDATED_AT = null;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return Builder<static> */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDay());
    }

    protected function casts(): array
    {
        return [
            'place' => Place::class,
            'created_at' => 'immutable_datetime',
        ];
    }
}
