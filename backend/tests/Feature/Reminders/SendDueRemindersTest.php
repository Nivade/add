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
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use Lorisleiva\Actions\Decorators\JobDecorator;

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

it('keeps the reminder on home across a reload, and drops it when dismissed', function (): void {
    CarbonImmutable::setTestNow('2026-09-19 13:02:00');

    $user = User::factory()->create();
    CalendarEvent::factory()->for($user)->create([
        'title' => 'Dentist',
        'starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
    ]);

    SendDueReminders::run($user);

    $reminder = null;

    foreach ([1, 2] as $visit) {
        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use (&$reminder): void {
                $page->where('home.reminder.title', 'Dentist')->has('home.reminder.lines', 4);

                $reminder = $page->toArray()['props']['home']['reminder']['id'];
            });
    }

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('reminders.dismiss', $reminder))
        ->assertRedirect(route('home'));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('home.reminder', null));
});

it('takes the reminder down once the appointment is behind them', function (): void {
    CarbonImmutable::setTestNow('2026-09-19 13:02:00');

    $user = User::factory()->create();
    CalendarEvent::factory()->for($user)->create([
        'title' => 'Dentist',
        'starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
    ]);

    SendDueReminders::run($user);

    $this->travelTo(CarbonImmutable::parse('2026-09-19 14:30:00'));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('home.reminder', null));
});

it('hides one person\'s reminder from another person\'s dismissal', function (): void {
    CarbonImmutable::setTestNow('2026-09-19 13:02:00');

    $user = User::factory()->create();
    CalendarEvent::factory()->for($user)->create(['starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00')]);

    SendDueReminders::run($user);

    $this->actingAs(User::factory()->create())
        ->post(route('reminders.dismiss', $user->unreadNotifications()->sole()->id))
        ->assertNotFound();

    expect($user->unreadNotifications()->count())->toBe(1);
});

it('queues one job per person rather than doing everybody inline', function (): void {
    Queue::fake();

    User::factory()->count(3)->create();

    $this->artisan('reminders:dispatch')->assertSuccessful();

    Queue::assertPushed(JobDecorator::class, 3);
});
