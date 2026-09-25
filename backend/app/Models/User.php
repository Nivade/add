<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\StoresDatesInUtc;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string $timezone
 * @property string|null $calendar_feed_url
 * @property Carbon|null $ai_consented_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'calendar_feed_url'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, StoresDatesInUtc, TwoFactorAuthenticatable;

    /** Every deadline, rung and reminder is read on their clock, never the server's. */
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone);
    }

    /** @return HasMany<Device, $this> */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function hasConsentedToAi(): bool
    {
        return $this->ai_consented_at !== null;
    }

    /** @return HasOne<ExecutionSession, $this> */
    public function runningSession(): HasOne
    {
        return $this->hasOne(ExecutionSession::class)
            ->ofMany(['started_at' => 'max', 'id' => 'max'], fn ($query) => $query->running());
    }

    /** Held while a session is opened, so two taps on Start cannot both find nothing running. */
    public function sessionLockKey(): string
    {
        return 'execution-session:'.$this->id;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'calendar_feed_url' => 'encrypted',
            'ai_consented_at' => 'datetime',
        ];
    }
}
