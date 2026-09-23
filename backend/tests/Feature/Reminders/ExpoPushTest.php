<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Device;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use Illuminate\Support\Facades\Http;

function notifyDevice(User $user, array $lines = ['Dentist is at 14:00.', 'Leaving is 28 minutes away.']): void
{
    $event = CalendarEvent::factory()->for($user)->create(['title' => 'Dentist']);

    $user->notify(new AppointmentReminder($event, $lines));
}

it('pushes the same lines the band shows, not a bare title', function (): void {
    Http::fake([
        'exp.host/*' => Http::response(['data' => [['status' => 'ok']]]),
    ]);

    $user = User::factory()->create();
    Device::factory()->for($user)->create(['push_token' => 'ExponentPushToken[abc]']);

    notifyDevice($user);

    Http::assertSent(function ($request): bool {
        expect($request->data()[0])->toMatchArray([
            'to' => 'ExponentPushToken[abc]',
            'title' => 'Dentist',
            'body' => "Dentist is at 14:00.\nLeaving is 28 minutes away.",
        ]);

        return true;
    });
});

it('stays off the network when nobody has a device', function (): void {
    Http::fake();

    notifyDevice(User::factory()->create());

    Http::assertNothingSent();
});

it('forgets a device that uninstalled the app', function (): void {
    Http::fake([
        'exp.host/*' => Http::response(['data' => [
            ['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']],
            ['status' => 'ok'],
        ]]),
    ]);

    $user = User::factory()->create();
    Device::factory()->for($user)->create(['push_token' => 'ExponentPushToken[gone]']);
    Device::factory()->for($user)->create(['push_token' => 'ExponentPushToken[here]']);

    notifyDevice($user);

    expect($user->devices()->pluck('push_token')->all())->toBe(['ExponentPushToken[here]']);
});
