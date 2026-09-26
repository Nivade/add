<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Database\Factories\FutureReminderFactory;
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
 * @property string $message
 * @property CarbonImmutable|null $trigger_at
 * @property string|null $calendar_event_id
 * @property int|null $offset_seconds
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Unguarded]
#[UseFactory(FutureReminderFactory::class)]
class FutureReminder extends Model
{
    /** @use HasFactory<FutureReminderFactory> */
    use HasFactory;

    use HasUlids;
    use StoresDatesInUtc;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<CalendarEvent, $this> */
    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    /** The instant this fires: the timestamp itself, or the calendar event's start plus its offset. */
    public function firesAt(): ?CarbonImmutable
    {
        if ($this->trigger_at instanceof CarbonImmutable) {
            return $this->trigger_at;
        }

        $event = $this->calendarEvent;

        if (! $event instanceof CalendarEvent) {
            return null;
        }

        return $event->starts_at->addSeconds($this->offset_seconds ?? 0);
    }

    /** @param  Builder<static>  $query */
    #[Scope]
    protected function unsent(Builder $query): void
    {
        $query->whereNull('sent_at');
    }

    protected function casts(): array
    {
        return [
            'trigger_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
        ];
    }
}
