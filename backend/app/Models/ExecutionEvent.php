<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExecutionEventType;
use Database\Factories\ExecutionEventFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
#[UseFactory(ExecutionEventFactory::class)]
class ExecutionEvent extends Model
{
    /** @use HasFactory<ExecutionEventFactory> */
    use HasFactory;

    use HasUlids;

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

    protected function casts(): array
    {
        return [
            'type' => ExecutionEventType::class,
            'payload' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
