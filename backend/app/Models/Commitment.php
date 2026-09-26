<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommitmentProvenance;
use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Database\Factories\CommitmentFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string $description
 * @property CommitmentProvenance $provenance
 * @property CarbonImmutable|null $confirmed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Unguarded]
#[UseFactory(CommitmentFactory::class)]
class Commitment extends Model
{
    /** @use HasFactory<CommitmentFactory> */
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
            'provenance' => CommitmentProvenance::class,
            'confirmed_at' => 'immutable_datetime',
        ];
    }
}
