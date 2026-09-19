<?php

declare(strict_types=1);

use App\Actions\Calendar\SyncCalendar;
use App\Contracts\CalendarSource;
use App\Data\Calendar\CalendarEventDraftData;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Support\Calendar\Sources\FakeCalendarSource;
use App\Support\Calendar\Sources\NullCalendarSource;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

function calendar(CalendarEventDraftData ...$events): FakeCalendarSource
{
    $source = new FakeCalendarSource;
    $source->push(...$events);

    app()->instance(CalendarSource::class, $source);

    return $source;
}

function draft(string $externalId, string $startsAt, string $title = 'Dentist'): CalendarEventDraftData
{
    return new CalendarEventDraftData(
        externalId: $externalId,
        title: $title,
        startsAt: CarbonImmutable::parse($startsAt),
    );
}

it('reads the day in and never writes anything back out', function (): void {
    $user = User::factory()->create();
    calendar(draft('abc-1', '2026-09-19 14:00:00'));

    $events = SyncCalendar::run($user, CarbonImmutable::parse('2026-09-19 09:00:00'));

    expect($events)->toHaveCount(1)
        ->and($events[0]->title)->toBe('Dentist')
        ->and($events[0]->source)->toBe('fake')
        ->and(CalendarEvent::query()->count())->toBe(1);
});

it('syncs the same event twice without duplicating it, and takes its new time', function (): void {
    $user = User::factory()->create();
    $now = CarbonImmutable::parse('2026-09-19 09:00:00');

    calendar(draft('abc-1', '2026-09-19 14:00:00'));
    SyncCalendar::run($user, $now);

    calendar(draft('abc-1', '2026-09-19 15:30:00'));
    SyncCalendar::run($user, $now);

    $event = CalendarEvent::query()->sole();

    expect(CalendarEvent::query()->count())->toBe(1)
        ->and($event->starts_at->toTimeString())->toBe('15:30:00');
});

it('drops an event the calendar stopped reporting', function (): void {
    $user = User::factory()->create();
    $now = CarbonImmutable::parse('2026-09-19 09:00:00');

    calendar(draft('abc-1', '2026-09-19 14:00:00'), draft('abc-2', '2026-09-19 16:00:00', 'Standup'));
    SyncCalendar::run($user, $now);

    calendar(draft('abc-1', '2026-09-19 14:00:00'));
    SyncCalendar::run($user, $now);

    expect(CalendarEvent::query()->pluck('external_id')->all())->toBe(['abc-1']);
});

it('leaves an event beyond the horizon out of the day it plans', function (): void {
    $user = User::factory()->create();

    calendar(draft('abc-9', '2026-09-30 14:00:00'));

    expect(SyncCalendar::run($user, CarbonImmutable::parse('2026-09-19 09:00:00')))->toBe([]);
});

it('answers an empty day when no calendar is connected', function (): void {
    app()->instance(CalendarSource::class, new NullCalendarSource);

    expect(SyncCalendar::run(User::factory()->create(), CarbonImmutable::parse('2026-09-19 09:00:00')))->toBe([]);
});

it('keeps one person\'s calendar out of another person\'s day', function (): void {
    $stranger = User::factory()->create();
    calendar(draft('abc-1', '2026-09-19 14:00:00'));
    SyncCalendar::run($stranger, CarbonImmutable::parse('2026-09-19 09:00:00'));

    expect(CalendarEvent::query()->where('user_id', User::factory()->create()->id)->count())->toBe(0);
});

it('shows the event as what is coming up, with a plan that can be corrected', function (): void {
    CarbonImmutable::setTestNow('2026-09-19 09:00:00');

    $user = User::factory()->create();
    calendar(draft('abc-1', '2026-09-19 14:00:00'));
    SyncCalendar::run($user);

    $event = CalendarEvent::query()->sole();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('home.comingUp.kind', 'calendar_event')
            ->where('home.comingUp.title', 'Dentist')
            ->where('home.comingUp.plan.rungs.2.clock', '13:30')
        );

    $this->actingAs($user)
        ->patchJson("/api/v1/calendar-events/{$event->id}/plan", ['leave' => 45])
        ->assertOk()
        ->assertJsonPath('kind', 'calendar_event')
        ->assertJsonPath('rungs.2.clock', '13:15')
        ->assertJsonPath('rungs.2.assumed', false);
});

it('does not let one person retime another person\'s appointment', function (): void {
    $event = CalendarEvent::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patchJson("/api/v1/calendar-events/{$event->id}/plan", ['leave' => 45])
        ->assertNotFound();
});
