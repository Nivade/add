<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IntentionStatus;
use App\Enums\StepStatus;
use Database\Factories\IntentionFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Unguarded]
#[UseFactory(IntentionFactory::class)]
class Intention extends Model
{
    /** @use HasFactory<IntentionFactory> */
    use HasFactory;

    use HasUlids;

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
            'deadline_at' => 'immutable_datetime',
            'decomposed_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
