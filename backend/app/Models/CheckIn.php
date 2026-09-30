<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CheckInAnswer;
use App\Enums\CheckInTopic;
use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Database\Factories\CheckInFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property CheckInTopic $topic
 * @property CheckInAnswer $answer
 * @property CarbonImmutable $created_at
 * @property-read User $user
 */
#[Unguarded]
#[UseFactory(CheckInFactory::class)]
class CheckIn extends Model
{
    /** @use HasFactory<CheckInFactory> */
    use HasFactory;

    use HasUlids;
    use StoresDatesInUtc;

    public const ?string UPDATED_AT = null;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'topic' => CheckInTopic::class,
            'answer' => CheckInAnswer::class,
            'created_at' => 'immutable_datetime',
        ];
    }
}
