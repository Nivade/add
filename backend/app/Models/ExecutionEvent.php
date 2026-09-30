<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExecutionEventType;
use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Database\Factories\ExecutionEventFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $execution_session_id
 * @property string|null $step_id
 * @property ExecutionEventType $type
 * @property array<string, mixed>|null $payload
 * @property CarbonImmutable $created_at
 */
#[Unguarded]
#[UseFactory(ExecutionEventFactory::class)]
class ExecutionEvent extends Model
{
    /** @use HasFactory<ExecutionEventFactory> */
    use HasFactory;

    use HasUlids;
    use StoresDatesInUtc;

    public const ?string UPDATED_AT = null;

    /** @return BelongsTo<ExecutionSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ExecutionSession::class, 'execution_session_id');
    }

    /** @return BelongsTo<Step, $this> */
    public function step(): BelongsTo
    {
        return $this->belongsTo(Step::class);
    }

    /** Two events can share a second; the ULID still orders them. */
    public function happenedAfter(self $other): bool
    {
        return $this->created_at->equalTo($other->created_at)
            ? strcmp($this->id, $other->id) > 0
            : $this->created_at->greaterThan($other->created_at);
    }

    protected function casts(): array
    {
        return [
            'type' => ExecutionEventType::class,
            'payload' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
