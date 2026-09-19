<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\PlansBackwards;
use App\Contracts\Appointment;
use App\Enums\AppointmentKind;
use App\Enums\IntentionStatus;
use App\Enums\StepStatus;
use Carbon\CarbonImmutable;
use Database\Factories\IntentionFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
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
 * @property bool $needs_clarification
 * @property CarbonImmutable|null $deadline_at
 * @property int|null $travel_seconds
 * @property int|null $preparation_seconds
 * @property int|null $gathering_seconds
 * @property CarbonImmutable|null $decomposed_at
 * @property CarbonImmutable|null $completed_at
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

    protected function casts(): array
    {
        return [
            'status' => IntentionStatus::class,
            'needs_clarification' => 'boolean',
            'deadline_at' => 'immutable_datetime',
            'decomposed_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
