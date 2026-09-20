<?php

declare(strict_types=1);

use App\Actions\Reminders\SendDueReminders;
use App\Models\CalendarEvent;
use App\Models\Intention;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

function sendReminders(User $user, string $now): array
{
    return SendDueReminders::run($user, CarbonImmutable::parse($now, $user->timezone));
}

it('carries the appointment, the preparation and the leave-by time, never a bare title', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    CalendarEvent::factory()->for($user)->create([
        'title' => 'Dentist',
        'starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
    ]);

    sendReminders($user, '2026-09-19 13:02:00');

    Notification::assertSentTo($user, AppointmentReminder::class, function (AppointmentReminder $notification) use ($user): bool {
        expect($notification->toArray($user)['lines'])->toBe([
            'Dentist is at 14:00.',
            'Start finding what you need.',
            'Leaving is 28 minutes away.',
            'If this waits, leaving at 13:30 waits with it.',
        ]);

        return true;
    });
});

it('stays quiet until the first preparation is actually due', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    CalendarEvent::factory()->for($user)->create(['starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00')]);

    expect(sendReminders($user, '2026-09-19 09:00:00'))->toBe([]);

    Notification::assertNothingSent();
});

it('sends one reminder for an appointment and not a stream of them', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    CalendarEvent::factory()->for($user)->create(['starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00')]);

    sendReminders($user, '2026-09-19 13:02:00');
    sendReminders($user, '2026-09-19 13:07:00');
    sendReminders($user, '2026-09-19 13:20:00');

    Notification::assertSentToTimes($user, AppointmentReminder::class, 1);
    expect(Reminder::query()->count())->toBe(1);
});

it('reminds about a dated intention on the same terms as a calendar event', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    Intention::factory()->active()->for($user)->create([
        'title' => 'Passport appointment',
        'deadline_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
    ]);

    $sent = sendReminders($user, '2026-09-19 13:02:00');

    expect($sent)->toHaveCount(1);
    Notification::assertSentTo($user, AppointmentReminder::class);
});

it('says nothing about an appointment that is not today', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    CalendarEvent::factory()->for($user)->create(['starts_at' => CarbonImmutable::parse('2026-09-21 14:00:00')]);

    expect(sendReminders($user, '2026-09-19 13:02:00'))->toBe([]);
    Notification::assertNothingSent();
});

it('does not remind one person about another person\'s day', function (): void {
    Notification::fake();

    CalendarEvent::factory()->create(['starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00')]);

    expect(sendReminders(User::factory()->create(), '2026-09-19 13:02:00'))->toBe([]);
    Notification::assertNothingSent();
});

it('puts the reminder on home once, and not again after it has been read', function (): void {
    CarbonImmutable::setTestNow('2026-09-19 13:02:00');

    $user = User::factory()->create();
    CalendarEvent::factory()->for($user)->create([
        'title' => 'Dentist',
        'starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
    ]);

    SendDueReminders::run($user);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('home.reminder.title', 'Dentist')
            ->has('home.reminder.lines', 4)
        );

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('home.reminder', null));
});
