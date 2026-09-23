<?php

declare(strict_types=1);

use App\Actions\Calendar\ConnectCalendarFeed;
use App\Actions\Calendar\DisconnectCalendarFeed;
use App\Actions\Calendar\SyncCalendar;
use App\Actions\Reminders\SendDueReminders;
use App\Contracts\CalendarSource;
use App\Models\CalendarEvent;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use App\Support\Calendar\Exceptions\CalendarFeedUnreadable;
use App\Support\Calendar\Sources\IcsCalendarSource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

const FEED_URL = 'https://calendar.example.test/private-abc123/basic.ics';

function icsFeed(string ...$events): string
{
    return implode("\r\n", ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//add//test//EN', ...$events, 'END:VCALENDAR'])."\r\n";
}

function feedPerson(?string $url = FEED_URL): User
{
    app()->instance(CalendarSource::class, new IcsCalendarSource);

    return User::factory()->create(['timezone' => 'Europe/Amsterdam', 'calendar_feed_url' => $url]);
}

it('reads recurrences, their overrides and Windows zone names as the calendar means them', function (): void {
    Http::preventStrayRequests();
    Http::fake([FEED_URL => Http::response(icsFeed(
        'BEGIN:VEVENT', 'UID:dentist-1', 'SUMMARY:Dentist', 'LOCATION:Kerkstraat 1',
        'DTSTART;TZID=W. Europe Standard Time:20260919T140000',
        'DTEND;TZID=W. Europe Standard Time:20260919T143000', 'END:VEVENT',
        'BEGIN:VEVENT', 'UID:standup', 'SUMMARY:Standup', 'DTSTART;TZID=Europe/Amsterdam:20260918T093000',
        'DURATION:PT15M', 'RRULE:FREQ=DAILY', 'EXDATE;TZID=Europe/Amsterdam:20260920T093000', 'END:VEVENT',
        'BEGIN:VEVENT', 'UID:standup', 'SUMMARY:Standup, moved', 'RECURRENCE-ID;TZID=Europe/Amsterdam:20260919T093000',
        'DTSTART;TZID=Europe/Amsterdam:20260919T110000', 'DURATION:PT15M', 'END:VEVENT',
        'BEGIN:VEVENT', 'UID:birthday', 'SUMMARY:Birthday', 'DTSTART;VALUE=DATE:20260919', 'END:VEVENT',
        'BEGIN:VEVENT', 'UID:called-off', 'SUMMARY:Called off', 'STATUS:CANCELLED', 'DTSTART:20260919T150000Z', 'END:VEVENT',
    ))]);

    $user = feedPerson();

    SyncCalendar::run($user, CarbonImmutable::parse('2026-09-19 09:00:00', 'Europe/Amsterdam'));

    expect(CalendarEvent::query()->oldest('starts_at')->get()->map(fn (CalendarEvent $event): array => [
        $event->external_id,
        $event->title,
        $event->starts_at->setTimezone('Europe/Amsterdam')->format('D H:i'),
        $event->ends_at?->setTimezone('Europe/Amsterdam')->format('H:i'),
    ])->all())->toBe([
        ['standup@20260919T073000Z', 'Standup, moved', 'Sat 11:00', '11:15'],
        ['dentist-1', 'Dentist', 'Sat 14:00', '14:30'],
        ['standup@20260921T073000Z', 'Standup', 'Mon 09:30', '09:45'],
    ]);
});

it('asks nothing of the network for a person who has not connected a feed', function (): void {
    Http::preventStrayRequests();

    $user = feedPerson(null);

    expect(SyncCalendar::run($user, CarbonImmutable::parse('2026-09-19 09:00:00')))->toBe([]);
});

it('asks an unchanged feed only whether it changed, and still reads the day from what it said last', function (): void {
    Http::preventStrayRequests();
    Http::fake([FEED_URL => fn (Request $request) => $request->hasHeader('If-None-Match', '"v1"')
        ? Http::response('', 304)
        : Http::response(icsFeed(
            'BEGIN:VEVENT', 'UID:dentist-1', 'SUMMARY:Dentist', 'DTSTART;TZID=Europe/Amsterdam:20260919T140000', 'END:VEVENT',
        ), 200, ['ETag' => '"v1"'])]);

    $user = feedPerson();
    $now = CarbonImmutable::parse('2026-09-19 09:00:00', 'Europe/Amsterdam');

    SyncCalendar::run($user, $now);
    $events = SyncCalendar::run($user, $now);

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('If-None-Match', '"v1"'));
    expect($events)->toHaveCount(1)
        ->and($events[0]->title)->toBe('Dentist')
        ->and(Cache::get('calendar-feed:'.hash('sha256', FEED_URL)))->toBeString()->not->toContain('Dentist');

    DisconnectCalendarFeed::run($user);
    ConnectCalendarFeed::run($user, FEED_URL);

    expect(Http::recorded()->last()[0]->hasHeader('If-None-Match'))->toBeFalse();
});

it('refuses an address that does not answer with a calendar, and keeps nothing of it', function (): void {
    Http::preventStrayRequests();
    Http::fake([FEED_URL => Http::response('<html>Sign in</html>')]);

    $user = feedPerson(null);

    $this->actingAs($user)
        ->put(route('calendar.update'), ['url' => FEED_URL])
        ->assertSessionHasErrors(['url' => 'That address did not answer with a calendar.']);

    expect($user->refresh()->calendar_feed_url)->toBeNull();
});

it('replaces a feed without leaving the old one on home, even when the new one reads empty', function (): void {
    $replacement = 'https://calendar.example.test/private-def456/basic.ics';
    Http::preventStrayRequests();
    Http::fake([
        FEED_URL => Http::response(icsFeed(
            'BEGIN:VEVENT', 'UID:dentist-1', 'SUMMARY:Dentist', 'DTSTART;TZID=Europe/Amsterdam:20260919T140000', 'END:VEVENT',
        )),
        $replacement => Http::response(icsFeed()),
    ]);
    $this->travelTo(CarbonImmutable::parse('2026-09-19 09:00:00', 'Europe/Amsterdam'));

    $user = feedPerson();
    SyncCalendar::run($user);

    ConnectCalendarFeed::run($user, $replacement);

    expect($user->refresh()->calendar_feed_url)->toBe($replacement)
        ->and(CalendarEvent::query()->count())->toBe(0);
});

it('fails loudly without repeating the private address when the feed cannot be read', function (mixed $response): void {
    Http::preventStrayRequests();
    Http::fake([FEED_URL => $response]);

    expect(fn () => SyncCalendar::run(feedPerson(), CarbonImmutable::parse('2026-09-19 09:00:00')))
        ->toThrow(fn (CalendarFeedUnreadable $exception) => expect($exception->getMessage())->not->toContain('private-abc123')
            ->and($exception->getPrevious())->toBeNull());
})->with([
    'gone' => fn () => Http::response('Not Found', 404),
    'unreachable' => fn () => Http::failedConnection('cURL error 6: Could not resolve host for '.FEED_URL),
    'not a calendar' => fn () => Http::response('<html>Sign in</html>'),
]);

it('puts a connected feed on home, reminds from it, and takes it all back on disconnect', function (): void {
    Http::preventStrayRequests();
    Http::fake([FEED_URL => Http::response(icsFeed(
        'BEGIN:VEVENT', 'UID:dentist-1', 'SUMMARY:Dentist',
        'DTSTART;TZID=Europe/Amsterdam:20260919T140000', 'DTEND;TZID=Europe/Amsterdam:20260919T143000', 'END:VEVENT',
    ))]);
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-09-19 09:00:00', 'Europe/Amsterdam'));

    $user = feedPerson(null);

    $this->actingAs($user)
        ->put(route('calendar.update'), ['url' => 'webcal://calendar.example.test/private-abc123/basic.ics'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('calendar.edit'));

    expect($user->refresh()->calendar_feed_url)->toBe(FEED_URL)
        ->and(DB::table('users')->where('id', $user->id)->value('calendar_feed_url'))->not->toContain('private-abc123');

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('home.comingUp.title', 'Dentist')
            ->where('home.comingUp.plan.rungs.2.clock', '13:30')
        );

    SendDueReminders::run($user, CarbonImmutable::parse('2026-09-19 13:02:00', 'Europe/Amsterdam'));
    Notification::assertSentTo($user, AppointmentReminder::class);

    $this->actingAs($user)
        ->delete(route('calendar.destroy'))
        ->assertRedirect(route('calendar.edit'));

    expect($user->refresh()->calendar_feed_url)->toBeNull()
        ->and(CalendarEvent::query()->count())->toBe(0)
        ->and(Reminder::query()->count())->toBe(0);
});

it('leaves another source\'s events alone on disconnect', function (): void {
    $user = feedPerson();
    CalendarEvent::factory()->for($user)->create(['source' => 'fixture']);

    $this->actingAs($user)->delete(route('calendar.destroy'));

    expect(CalendarEvent::query()->count())->toBe(1);
});

it('names the host it reads from and never hands the private address back', function (): void {
    $this->actingAs(feedPerson())
        ->get(route('calendar.edit'))
        ->assertOk()
        ->assertDontSee('private-abc123')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/calendar')
            ->where('connectedHost', 'calendar.example.test')
        );
});

it('takes only an address a calendar publishes over https or webcal', function (string $url): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('calendar.update'), ['url' => $url])
        ->assertSessionHasErrors(['url' => 'The url field must be a valid URL.']);

    expect($user->refresh()->calendar_feed_url)->toBeNull();
})->with([
    'plain http' => 'http://calendar.example.test/basic.ics',
    'a file' => 'file:///etc/passwd',
    'not an address' => 'my calendar',
]);

it('sends a guest to sign in', function (): void {
    $this->get(route('calendar.edit'))->assertRedirect(route('login'));
});
