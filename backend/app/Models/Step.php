<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StepStatus;
use Database\Factories\StepFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
