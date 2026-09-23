<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\PersonalAccessToken;
use PragmaRX\Google2FA\Google2FA;

function credentials(array $overrides = []): array
{
    return array_merge([
        'email' => 'someone@example.com',
        'password' => 'a-real-password',
        'device_name' => 'Pixel 9',
    ], $overrides);
}

it('hands a device a token it can then read the API with', function (): void {
    User::factory()->create(['email' => 'someone@example.com', 'password' => 'a-real-password']);

    $token = $this->postJson('/api/v1/tokens', credentials())
        ->assertCreated()
        ->assertJsonPath('deviceName', 'Pixel 9')
        ->json('token');

    expect($token)->toBeString();

    $this->withToken($token)->getJson('/api/v1/next-action')->assertOk();
});

it('refuses a wrong password without saying which half was wrong', function (): void {
    User::factory()->create(['email' => 'someone@example.com', 'password' => 'a-real-password']);

    $this->postJson('/api/v1/tokens', credentials(['password' => 'not-it']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('will not trade a second factor for a token', function (): void {
    $secret = app(TwoFactorAuthenticationProvider::class)->generateSecretKey();

    User::factory()->create([
        'email' => 'someone@example.com',
        'password' => 'a-real-password',
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->postJson('/api/v1/tokens', credentials())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    $this->postJson('/api/v1/tokens', credentials([
        'code' => app(Google2FA::class)->getCurrentOtp($secret),
    ]))->assertCreated();
});

it('signs out one device and leaves the others signed in', function (): void {
    $user = User::factory()->create();

    $phone = $user->createToken('Pixel 9')->plainTextToken;
    $tablet = $user->createToken('iPad')->plainTextToken;

    $this->withToken($phone)->deleteJson('/api/v1/tokens/current')->assertNoContent();

    expect(PersonalAccessToken::findToken($phone))->toBeNull();

    $this->withToken($tablet)->getJson('/api/v1/next-action')->assertOk();
});
