<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Appointment;
use App\Enums\AppointmentKind;
use App\Enums\IntentionStatus;
use App\Enums\StepStatus;
use App\Models\Concerns\PlansBackwards;
use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Database\Factories\IntentionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property int $user_id
 * @property string $title
 * @property string|null $why
 * @property IntentionStatus $status
 * @property string|null $clarifying_question
 * @property string|null $clarification
 * @property-read bool $needs_clarification
 * @property CarbonImmutable|null $deadline_at
 * @property CarbonImmutable|null $deadline_confirmed_at
 * @property-read bool $deadline_inferred
 * @property int|null $travel_seconds
 * @property int|null $preparation_seconds
 * @property int|null $gathering_seconds
 * @property CarbonImmutable|null $decomposed_at
 * @property CarbonImmutable|null $completed_at
 * @property int|null $recurrence_every_days
 * @property CarbonImmutable|null $recurrence_next_at
 * @property-read bool $is_recurring
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Unguarded]
#[UseFactory(IntentionFactory::class)]
class Intention extends Model implements Appointment
{
    /** @use HasFactory<IntentionFactory> */
    use HasFactory;

    use HasUlids;
    use PlansBackwards;
    use StoresDatesInUtc;

    public function appointmentKind(): AppointmentKind
    {
        return AppointmentKind::Intention;
    }

    public function appointmentId(): string
    {
        return $this->id;
    }

    public function appointmentTitle(): string
    {
        return $this->title;
    }

    public function appointmentAt(): ?CarbonImmutable
    {
        return $this->deadline_at;
    }

    public function appointmentInferred(): bool
    {
        return $this->deadline_at !== null && $this->deadline_confirmed_at === null;
    }

    /** @return Attribute<bool, never> */
    protected function deadlineInferred(): Attribute
    {
        return Attribute::get(fn (): bool => $this->appointmentInferred());
    }

    /** @return Attribute<bool, never> */
    protected function needsClarification(): Attribute
    {
        return Attribute::get(fn (): bool => $this->clarifying_question !== null && $this->clarification === null);
    }

    /** @return Attribute<bool, never> */
    protected function isRecurring(): Attribute
    {
        return Attribute::get(fn (): bool => $this->recurrence_every_days !== null);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Step, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(Step::class)->orderBy('position');
    }

    /** @return HasMany<ExecutionSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(ExecutionSession::class);
    }

    /** @return HasMany<Step, $this> */
    public function remainingSteps(): HasMany
    {
        return $this->steps()->where('status', StepStatus::Pending);
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', [IntentionStatus::Captured, IntentionStatus::Active]);
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function awaitingClarification(Builder $query): void
    {
        $query->whereNotNull('clarifying_question')->whereNull('clarification');
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function notAwaitingClarification(Builder $query): void
    {
        $query->whereNot(fn (Builder $query): Builder => $query->awaitingClarification());
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function dueForRecurrence(Builder $query, CarbonImmutable $now): void
    {
        $query->whereNotNull('recurrence_every_days')->where('recurrence_next_at', '<=', $now);
    }

    protected function casts(): array
    {
        return [
            'status' => IntentionStatus::class,
            'deadline_at' => 'immutable_datetime',
            'deadline_confirmed_at' => 'immutable_datetime',
            'decomposed_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'recurrence_next_at' => 'immutable_datetime',
        ];
    }
}
