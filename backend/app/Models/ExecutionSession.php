<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SessionOutcome;
use Database\Factories\ExecutionSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Unguarded]
#[UseFactory(ExecutionSessionFactory::class)]
class ExecutionSession extends Model
{
    /** @use HasFactory<ExecutionSessionFactory> */
    use HasFactory;

    use HasUlids;

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

    /** @return BelongsTo<Step, $this> */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(Step::class, 'current_step_id');
    }

    /** @return HasMany<ExecutionEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ExecutionEvent::class);
    }

    public function isRunning(): bool
    {
        return $this->ended_at === null;
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function running(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    protected function casts(): array
    {
        return [
            'outcome' => SessionOutcome::class,
            'started_at' => 'immutable_datetime',
            'paused_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }
}
