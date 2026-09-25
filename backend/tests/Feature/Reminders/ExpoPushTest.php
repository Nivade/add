<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Device;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use App\Support\Time\BackwardsPlan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

function notifyDevice(User $user): void
{
    $now = CarbonImmutable::parse('2026-09-19 13:02:00', $user->timezone);
    $event = CalendarEvent::factory()->for($user)->create([
        'title' => 'Dentist',
        'starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
    ]);

    $plan = BackwardsPlan::for($event, $now) ?? throw new RuntimeException('expected a same-day plan');

    $user->notify(new AppointmentReminder($event, $plan, $now));
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
            'body' => "Dentist is at 14:00.\nStart finding what you need.\nLeaving is 28 minutes away.\nIf this waits, leaving at 13:30 waits with it.",
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
