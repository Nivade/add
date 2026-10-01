<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\Ai\ParsedCaptureData;
use App\Enums\CaptureKind;
use App\Enums\CaptureSource;
use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Database\Factories\CaptureFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $user_id
 * @property string $body
 * @property CaptureSource $source
 * @property ?string $intention_id
 * @property ?CaptureKind $kind
 * @property ?string $routed_id
 * @property ?ParsedCaptureData $parsed
 * @property ?CarbonImmutable $processed_at
 * @property ?CarbonImmutable $kind_confirmed_at
 * @property ?CarbonImmutable $failed_at
 * @property CarbonImmutable $created_at
 */
#[Unguarded]
#[UseFactory(CaptureFactory::class)]
class Capture extends Model
{
    /** @use HasFactory<CaptureFactory> */
    use HasFactory;

    use HasUlids;
    use StoresDatesInUtc;

    public const ?string UPDATED_AT = null;

    private const int EXCERPT_CHARACTERS = 80;

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

    /** The row sorting wrote for it; which table that is depends on the kind. */
    public function routed(): Intention|WaitingFor|Commitment|FutureReminder|null
    {
        return match ($this->kind) {
            CaptureKind::Thought => Intention::query()->find($this->routed_id),
            CaptureKind::WaitingFor => WaitingFor::query()->find($this->routed_id),
            CaptureKind::Promise => Commitment::query()->find($this->routed_id),
            CaptureKind::Reminder => FutureReminder::query()->find($this->routed_id),
            CaptureKind::NotForYou, null => null,
        };
    }

    public function excerpt(): string
    {
        return Str::limit($this->body, self::EXCERPT_CHARACTERS);
    }

    /**
     * Sorted into something other than a thought, and not yet answered: a thought shows up as its first step instead.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function awaitingReadBack(Builder $query): void
    {
        $query->whereNotNull('kind')
            ->where('kind', '!=', CaptureKind::Thought)
            ->whereNull('kind_confirmed_at');
    }

    protected function casts(): array
    {
        return [
            'source' => CaptureSource::class,
            'kind' => CaptureKind::class,
            'parsed' => ParsedCaptureData::class,
            'processed_at' => 'immutable_datetime',
            'kind_confirmed_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
