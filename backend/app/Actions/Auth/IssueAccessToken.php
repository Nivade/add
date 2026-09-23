<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Lorisleiva\Actions\Concerns\AsObject;

/** The device's way in. A token is the whole credential from here on, so the second factor is checked before one exists. */
final class IssueAccessToken
{
    use AsObject;

    public function handle(string $email, string $password, string $deviceName, ?string $code = null): string
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $this->verifySecondFactor($user, $code);
        }

        return $user->createToken($deviceName)->plainTextToken;
    }

    private function verifySecondFactor(User $user, ?string $code): void
    {
        $secret = $user->two_factor_secret;

        if ($code === null || $secret === null) {
            throw ValidationException::withMessages(['code' => __('The two factor authentication code is required.')]);
        }

        $verified = app(TwoFactorAuthenticationProvider::class)
            ->verify(Fortify::currentEncrypter()->decrypt($secret), $code);

        if (! $verified) {
            throw ValidationException::withMessages(['code' => __('The provided two factor authentication code was invalid.')]);
        }
    }
}
