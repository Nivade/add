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
 * @property CarbonImmutable $trigger_at
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

    /** @param  Builder<static>  $query */
    #[Scope]
    protected function due(Builder $query, CarbonImmutable $now): void
    {
        $query->whereNull('sent_at')->where('trigger_at', '<=', $now);
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
