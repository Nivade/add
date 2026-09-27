<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommitmentProvenance;
use App\Enums\CommitmentStatus;
use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Database\Factories\CommitmentFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string|null $intention_id
 * @property string|null $step_id
 * @property string $description
 * @property CommitmentProvenance $provenance
 * @property CarbonImmutable|null $confirmed_at
 * @property CommitmentStatus $status
 * @property-read bool $awaiting_confirmation
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

    /** @return Attribute<bool, never> */
    protected function awaitingConfirmation(): Attribute
    {
        return Attribute::get(fn (): bool => $this->provenance->isInferred() && $this->confirmed_at === null);
    }

    /** @param  Builder<static>  $query */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', CommitmentStatus::Open);
    }

    /** @param  Builder<static>  $query */
    #[Scope]
    protected function forIntention(Builder $query, string $intentionId): void
    {
        $query->where('intention_id', $intentionId);
    }

    /** @param  Builder<static>  $query */
    #[Scope]
    protected function forStep(Builder $query, string $stepId): void
    {
        $query->where('step_id', $stepId);
    }

    /**
     * An unconfirmed inference is the one thing that must not sit quietly, so it comes first.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function mostPressingFirst(Builder $query): void
    {
        $query->orderByRaw('confirmed_at is not null')->oldest()->orderBy('id');
    }

    protected function casts(): array
    {
        return [
            'provenance' => CommitmentProvenance::class,
            'status' => CommitmentStatus::class,
            'confirmed_at' => 'immutable_datetime',
        ];
    }
}
