<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WaitingForStatus;
use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Database\Factories\WaitingForFactory;
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
 * @property string $subject
 * @property string|null $note
 * @property WaitingForStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Unguarded]
#[UseFactory(WaitingForFactory::class)]
class WaitingFor extends Model
{
    /** @use HasFactory<WaitingForFactory> */
    use HasFactory;

    use HasUlids;
    use StoresDatesInUtc;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'status' => WaitingForStatus::class,
        ];
    }

    /**
     * Cancelled and received are retired; only these two still ask for attention.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', [WaitingForStatus::Waiting, WaitingForStatus::FollowedUp]);
    }

    /**
     * `updated_at` is the last time this was touched, by creation or by a response — the one clock this needs.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function stale(Builder $query, CarbonImmutable $now, int $days): void
    {
        $query->whereIn('status', [WaitingForStatus::Waiting, WaitingForStatus::FollowedUp])
            ->where('updated_at', '<=', $now->subDays($days));
    }
}
