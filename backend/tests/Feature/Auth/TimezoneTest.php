<?php

declare(strict_types=1);

use App\Models\User;

it('reads the zone from the cookie the web page sets', function (): void {
    $user = User::factory()->create(['timezone' => 'UTC']);

    $this->actingAs($user)
        ->withUnencryptedCookie('tz', 'Europe/Amsterdam')
        ->get(route('home'))
        ->assertOk();

    expect($user->refresh()->timezone)->toBe('Europe/Amsterdam');
});

it('reads the zone from the header the phone sends', function (): void {
    $user = User::factory()->create(['timezone' => 'UTC']);

    $this->actingAs($user)
        ->withHeader('X-Timezone', 'America/New_York')
        ->getJson('/api/v1/home')
        ->assertOk();

    expect($user->refresh()->timezone)->toBe('America/New_York');
});

it('stores the current name for a zone a device reports by its old one', function (): void {
    $user = User::factory()->create(['timezone' => 'UTC']);

    $this->actingAs($user)
        ->withHeader('X-Timezone', 'Asia/Calcutta')
        ->getJson('/api/v1/home')
        ->assertOk();

    expect($user->refresh()->timezone)->toBe('Asia/Kolkata');
});

it('keeps a zone PHP already knows under the name the device sent', function (): void {
    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);

    $this->actingAs($user)
        ->withHeader('X-Timezone', 'UTC')
        ->getJson('/api/v1/home')
        ->assertOk();

    expect($user->refresh()->timezone)->toBe('UTC');
});

it('ignores a zone that does not exist', function (): void {
    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);

    $this->actingAs($user)
        ->withHeader('X-Timezone', 'Mars/Olympus_Mons')
        ->getJson('/api/v1/home')
        ->assertOk();

    expect($user->refresh()->timezone)->toBe('Europe/Amsterdam');
});

it('leaves every account alone on a guest request', function (): void {
    $user = User::factory()->create(['timezone' => 'UTC']);

    $this->withUnencryptedCookie('tz', 'Europe/Amsterdam')
        ->get(route('home'))
        ->assertRedirect(route('login'));

    expect($user->refresh()->timezone)->toBe('UTC');
});
