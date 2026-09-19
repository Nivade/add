<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StepStatus;
use Carbon\CarbonImmutable;
use Database\Factories\StepFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $intention_id
 * @property string $title
 * @property int $position
 * @property int|null $estimated_seconds
 * @property StepStatus $status
 * @property int $skip_count
 * @property bool $generated
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $last_skipped_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Intention $intention
 */
#[Unguarded]
#[UseFactory(StepFactory::class)]
class Step extends Model
{
    /** @use HasFactory<StepFactory> */
    use HasFactory;

    use HasUlids;

    /** @return BelongsTo<Intention, $this> */
    public function intention(): BelongsTo
    {
        return $this->belongsTo(Intention::class);
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', StepStatus::Pending);
    }

    protected function casts(): array
    {
        return [
            'status' => StepStatus::class,
            'generated' => 'boolean',
            'completed_at' => 'immutable_datetime',
            'last_skipped_at' => 'immutable_datetime',
        ];
    }
}
