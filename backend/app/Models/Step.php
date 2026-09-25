<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\Ai\ParsedStepData;
use App\Enums\StepStatus;
use App\Models\Concerns\StoresDatesInUtc;
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
    use StoresDatesInUtc;

    /** @return BelongsTo<Intention, $this> */
    public function intention(): BelongsTo
    {
        return $this->belongsTo(Intention::class);
    }

    /** `position` is the order the model gave, which is a claim about sequence and not about priority. */
    public static function generate(Intention $intention, ParsedStepData $parsed, int $position): self
    {
        return $intention->steps()->create([
            'title' => $parsed->title,
            'position' => $position,
            'estimated_seconds' => $parsed->estimatedSeconds,
            'status' => StepStatus::Pending,
            'generated' => true,
        ]);
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
