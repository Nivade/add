<?php

declare(strict_types=1);

use App\Enums\DevicePlatform;
use App\Models\Device;
use App\Models\User;

it('registers a device for push and never echoes its token back', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/devices', [
            'push_token' => 'ExponentPushToken[abc]',
            'platform' => 'ios',
        ])
        ->assertCreated()
        ->assertJsonPath('platform', 'ios')
        ->assertJsonMissingPath('pushToken');

    expect($user->devices()->sole()->push_token)->toBe('ExponentPushToken[abc]');
});

it('moves a reinstalled device rather than duplicating it', function (): void {
    $first = User::factory()->create();
    $second = User::factory()->create();

    $this->actingAs($first)->postJson('/api/v1/devices', [
        'push_token' => 'ExponentPushToken[abc]',
        'platform' => 'ios',
    ])->assertCreated();

    $this->actingAs($second)->postJson('/api/v1/devices', [
        'push_token' => 'ExponentPushToken[abc]',
        'platform' => 'android',
    ])->assertCreated();

    expect(Device::query()->count())->toBe(1)
        ->and($second->devices()->sole()->platform)->toBe(DevicePlatform::Android)
        ->and($first->devices()->count())->toBe(0);
});

it('refuses an unauthenticated device', function (): void {
    $this->postJson('/api/v1/devices', ['push_token' => 'ExponentPushToken[abc]', 'platform' => 'ios'])
        ->assertUnauthorized();
});
