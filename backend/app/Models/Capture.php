<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CaptureSource;
use Database\Factories\CaptureFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
#[UseFactory(CaptureFactory::class)]
class Capture extends Model
{
    /** @use HasFactory<CaptureFactory> */
    use HasFactory;

    use HasUlids;

    public const ?string UPDATED_AT = null;

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

    protected function casts(): array
    {
        return [
            'source' => CaptureSource::class,
            'processed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
