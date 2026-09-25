<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SessionOutcome;
use App\Exceptions\InvalidSessionTransition;
use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
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

/**
 * @property string $id
 * @property int $user_id
 * @property string $intention_id
 * @property string|null $current_step_id
 * @property SessionOutcome|null $outcome
 * @property int $steps_completed
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $paused_at
 * @property CarbonImmutable|null $ended_at
 * @property-read Intention $intention
 * @property-read User $user
 */
#[Unguarded]
#[UseFactory(ExecutionSessionFactory::class)]
class ExecutionSession extends Model
{
    /** @use HasFactory<ExecutionSessionFactory> */
    use HasFactory;

    use HasUlids;
    use StoresDatesInUtc;

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

    public function assertOpen(): void
    {
        if (! $this->isRunning()) {
            throw new InvalidSessionTransition("Session {$this->id} has already ended.");
        }
    }

    public function currentStepOrFail(): Step
    {
        $this->assertOpen();

        $step = $this->currentStep()->getResults();

        if (! $step instanceof Step) {
            throw new InvalidSessionTransition("Session {$this->id} is not pointing at a step.");
        }

        return $step;
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
