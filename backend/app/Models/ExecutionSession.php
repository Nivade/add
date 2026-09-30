<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SessionOutcome;
use App\Exceptions\InvalidSessionTransition;
use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Closure;
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
use Illuminate\Support\Facades\DB;

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

    private bool $locked = false;

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

    /**
     * Runs $work on this row re-read under a lock, so two taps cannot both pass the open check. A nested call reuses the lock.
     *
     * @template T
     *
     * @param  Closure(): T  $work
     * @return T
     */
    public function transition(Closure $work): mixed
    {
        if ($this->locked) {
            $this->assertOpen();

            return $work();
        }

        return DB::transaction(function () use ($work): mixed {
            $locked = self::query()->lockForUpdate()->findOrFail($this->id);

            $this->setRawAttributes($locked->getAttributes(), sync: true);
            $this->setRelations([]);
            $this->assertOpen();
            $this->locked = true;

            try {
                return $work();
            } finally {
                $this->locked = false;
            }
        });
    }

    private function assertOpen(): void
    {
        if (! $this->isRunning()) {
            throw new InvalidSessionTransition("Session {$this->id} has already ended.");
        }
    }

    public function currentStepOrFail(?string $expectedStepId = null): Step
    {
        $this->assertOpen();

        $step = $this->currentStep()->getResults();

        if (! $step instanceof Step) {
            throw new InvalidSessionTransition("Session {$this->id} is not pointing at a step.");
        }

        if ($expectedStepId !== null && $step->id !== $expectedStepId) {
            throw new InvalidSessionTransition("Session {$this->id} has moved past step {$expectedStepId}.");
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
