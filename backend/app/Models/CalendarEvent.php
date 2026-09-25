<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Appointment;
use App\Enums\AppointmentKind;
use App\Models\Concerns\PlansBackwards;
use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Database\Factories\CalendarEventFactory;
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
 * @property int $user_id
 * @property string $source
 * @property string $external_id
 * @property string $title
 * @property string|null $location
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property int|null $travel_seconds
 * @property int|null $preparation_seconds
 * @property int|null $gathering_seconds
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Unguarded]
#[UseFactory(CalendarEventFactory::class)]
class CalendarEvent extends Model implements Appointment
{
    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    use HasUlids;
    use PlansBackwards;
    use StoresDatesInUtc;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointmentKind(): AppointmentKind
    {
        return AppointmentKind::CalendarEvent;
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
        return $this->starts_at;
    }

    /** The calendar stated this time; nothing about it was read out of a sentence. */
    public function appointmentInferred(): bool
    {
        return false;
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function ofSource(Builder $query, User $user, string $source): void
    {
        $query->where('user_id', $user->id)->where('source', $source);
    }

    /**
     * Reminders point at an appointment without a foreign key, so they go in the same breath as the events.
     *
     * @param  Builder<self>  $events
     */
    public static function forget(Builder $events): void
    {
        Reminder::query()
            ->where('appointment_kind', AppointmentKind::CalendarEvent)
            ->whereIn('appointment_id', $events->clone()->select('id'))
            ->delete();

        $events->delete();
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }
}
