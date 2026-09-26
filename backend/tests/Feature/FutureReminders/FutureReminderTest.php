<?php

declare(strict_types=1);

use App\Actions\FutureReminders\SendDueFutureReminders;
use App\Models\CalendarEvent;
use App\Models\FutureReminder;
use App\Models\User;
use App\Notifications\FutureReminderDue;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

it('creates a time-triggered reminder from a phrase carrying its own time', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-25 09:00:00', 'Europe/Amsterdam'));
    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('future-reminders.store'), ['text' => 'tomorrow at 5, buy dishwasher tablets'])
        ->assertRedirect(route('home'));

    $reminder = FutureReminder::query()->sole();

    expect($reminder->message)->toBe('buy dishwasher tablets')
        ->and($reminder->trigger_at?->setTimezone('Europe/Amsterdam')->format('Y-m-d H:i'))->toBe('2026-09-26 05:00')
        ->and($reminder->calendar_event_id)->toBeNull();
});

it('rejects a reminder with no time anywhere in it', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('future-reminders.store'), ['text' => 'buy dishwasher tablets'])
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors('text');

    expect(FutureReminder::query()->count())->toBe(0);
});

it('creates a calendar-relative reminder from the picked event and offset', function (): void {
    $user = User::factory()->create();
    $event = CalendarEvent::factory()->for($user)->create(['title' => 'Dentist']);

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('calendar-events.future-reminder', $event), [
            'message' => 'call the pharmacy',
            'offset_minutes' => 30,
        ])
        ->assertRedirect(route('home'));

    $reminder = FutureReminder::query()->sole();

    expect($reminder->message)->toBe('call the pharmacy')
        ->and($reminder->calendar_event_id)->toBe($event->id)
        ->and($reminder->offset_seconds)->toBe(1800)
        ->and($reminder->trigger_at)->toBeNull();
});

it('does not let one person attach a reminder to another person\'s calendar event', function (): void {
    $event = CalendarEvent::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('calendar-events.future-reminder', $event), [
            'message' => 'call the pharmacy',
            'offset_minutes' => 30,
        ])
        ->assertNotFound();

    expect(FutureReminder::query()->count())->toBe(0);
});

it('fires a time-triggered reminder at the right instant for a non-UTC user', function (): void {
    Notification::fake();

    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);
    $reminder = FutureReminder::factory()->for($user)->create([
        'message' => 'buy dishwasher tablets',
        'trigger_at' => CarbonImmutable::parse('2026-09-26 05:00:00', 'Europe/Amsterdam'),
    ]);

    SendDueFutureReminders::run($user, CarbonImmutable::parse('2026-09-26 04:59:00', 'Europe/Amsterdam'));
    Notification::assertNothingSent();

    SendDueFutureReminders::run($user, CarbonImmutable::parse('2026-09-26 05:00:00', 'Europe/Amsterdam'));

    Notification::assertSentTo($user, FutureReminderDue::class, function (FutureReminderDue $notification) use ($user): bool {
        expect($notification->toArray($user)['message'])->toBe('buy dishwasher tablets');

        return true;
    });

    expect($reminder->refresh()->sent_at)->not->toBeNull();
});

it('fires a calendar-relative reminder off the event\'s start, not the reminder\'s own clock', function (): void {
    Notification::fake();

    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);
    $event = CalendarEvent::factory()->for($user)->create([
        'starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
    ]);
    FutureReminder::factory()->for($user)->create([
        'message' => 'call the pharmacy',
        'trigger_at' => null,
        'calendar_event_id' => $event->id,
        'offset_seconds' => 1800,
    ]);

    SendDueFutureReminders::run($user, CarbonImmutable::parse('2026-09-19 14:29:00'));
    Notification::assertNothingSent();

    SendDueFutureReminders::run($user, CarbonImmutable::parse('2026-09-19 14:30:00'));
    Notification::assertSentTo($user, FutureReminderDue::class);
});

it('sends a due reminder once and not on every dispatch after', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    FutureReminder::factory()->for($user)->create(['trigger_at' => CarbonImmutable::parse('2026-09-19 09:00:00')]);

    SendDueFutureReminders::run($user, CarbonImmutable::parse('2026-09-19 10:00:00'));
    SendDueFutureReminders::run($user, CarbonImmutable::parse('2026-09-19 11:00:00'));

    Notification::assertSentToTimes($user, FutureReminderDue::class, 1);
});

it('refuses a row with both or neither trigger set, even bypassing the action', function (): void {
    $user = User::factory()->create();
    $event = CalendarEvent::factory()->for($user)->create();

    expect(fn () => FutureReminder::factory()->for($user)->create([
        'trigger_at' => CarbonImmutable::now(),
        'calendar_event_id' => $event->id,
        'offset_seconds' => 1800,
    ]))->toThrow(Illuminate\Database\QueryException::class);

    expect(fn () => FutureReminder::factory()->for($user)->create([
        'trigger_at' => null,
        'calendar_event_id' => null,
    ]))->toThrow(Illuminate\Database\QueryException::class);
});

it('answers over the API too', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.future-reminders.store'), ['text' => 'in 2 hours, call the dentist'])
        ->assertCreated()
        ->assertJsonPath('message', 'call the dentist');
});
