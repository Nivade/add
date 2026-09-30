<?php

declare(strict_types=1);

use App\Actions\Reminders\SendDueReminders;
use App\Actions\Sessions\StartSession;
use App\Enums\CaptureKind;
use App\Models\CalendarEvent;
use App\Models\Capture;
use App\Models\Commitment;
use App\Models\ExecutionSession;
use App\Models\FutureReminder;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use App\Models\WaitingFor;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

it('pitches the product to a guest and sends a signed-in person to their answer', function (): void {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->component('welcome'));

    $this->actingAs(User::factory()->create())
        ->get(route('welcome'))
        ->assertRedirect(route('home'));
});

it('sends a guest to the login page', function (): void {
    $this->get(route('home'))->assertRedirect(route('login'));
});

it('answers with one thing and says why it is that one', function (): void {
    $intention = kitchen();

    $this->actingAs($intention->user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('home')
            ->where('home.rightNow.step.title', 'Step 1.')
            ->where('home.rightNow.intention.title', 'Clean the kitchen')
            ->where('home.rightNow.why', [
                'It is the first thing left in this one.',
                'This takes about 1 minute.',
            ])
            ->where('home.session', null)
        );
});

it('offers the open session instead of choosing again', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('home.session.session.id', $session->id)
            ->where('home.session.intention.title', 'Clean the kitchen')
        );
});

it('names the next real deadline and nothing else about time', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-19 09:00:00'));

    $user = User::factory()->create();
    $soon = Intention::factory()->decomposed()->for($user)->create([
        'title' => 'Renew the passport',
        'deadline_at' => CarbonImmutable::now()->addDays(2),
    ]);
    Step::factory()->for($soon)->create(['position' => 1]);

    Intention::factory()->decomposed()->for($user)->create([
        'title' => 'Book the dentist',
        'deadline_at' => CarbonImmutable::now()->addMonth(),
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('home.comingUp.title', 'Renew the passport')
            ->where('home.comingUp.inWords', '2 days from now')
        );
});

it('holds an intention nobody could name in its own band, never as the thing to do', function (): void {
    $user = User::factory()->create();
    $unclear = Intention::factory()->decomposed()->unclear()->for($user)->create(['title' => 'Sort the thing out']);
    Step::factory()->for($unclear)->create(['position' => 1]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('home.rightNow', null)
            ->has('home.needsAttention', 1)
            ->where('home.needsAttention.0.title', 'Sort the thing out')
            ->where('home.restCount', 0)
        );
});

it('counts everything else without listing it', function (): void {
    $user = User::factory()->create();
    Intention::factory()->count(6)->decomposed()->for($user)->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('home.restCount', 6)
            ->has('home.needsAttention', 0)
        );
});

it('counts the captures still being sorted, and only those', function (): void {
    $user = User::factory()->create();
    Capture::factory()->count(2)->for($user)->create();
    Capture::factory()->for($user)->create(['processed_at' => CarbonImmutable::now()]);
    Capture::factory()->for($user)->create(['created_at' => CarbonImmutable::now()->subDays(2)]);
    Capture::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('home.sortingCount', 2));
});

it('reads back what it sorted and has not heard about yet, three at most, with the rest counted', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 4) as $minutesAgo) {
        Capture::factory()->for($user)->create([
            'body' => "waiting for John, {$minutesAgo}",
            'kind' => CaptureKind::WaitingFor,
            'routed_id' => WaitingFor::factory()->for($user)->create()->id,
            'processed_at' => now(),
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    Capture::factory()->for($user)->create(['kind' => CaptureKind::Thought, 'processed_at' => now()]);
    Capture::factory()->for($user)->create(['kind' => CaptureKind::Promise, 'processed_at' => now(), 'kind_confirmed_at' => now()]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('home.sorted', 3)
            ->where('home.sorted.0.excerpt', 'waiting for John, 1')
            ->where('home.sorted.0.kind', 'waiting_for')
            ->where('home.sorted.0.detail', 'John')
            ->where('home.sortedMore', 1)
        );
});

it('says when a sorted reminder will come, on the clock the person reads', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-16 10:00:00', 'UTC'));
    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);
    $reminder = FutureReminder::factory()->for($user)->create(['trigger_at' => CarbonImmutable::parse('2026-09-17 07:00:00', 'UTC')]);
    Capture::factory()->for($user)->create(['kind' => CaptureKind::Reminder, 'routed_id' => $reminder->id, 'processed_at' => now()]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('home.sorted.0.detail', 'tomorrow at 09:00'));
});

it('asks about an inferred promise in its read-back, not in Needs you as well', function (): void {
    $user = User::factory()->create();
    $commitment = Commitment::factory()->inferred()->for($user)->create();
    $capture = Capture::factory()->for($user)->create(['kind' => CaptureKind::Promise, 'routed_id' => $commitment->id, 'processed_at' => now()]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('home.sorted', 1)
            ->has('home.needsAttention', 0)
        );

    $capture->update(['kind_confirmed_at' => now()]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('home.sorted', 0)
            ->where('home.needsAttention.0.kind', 'commitment')
        );
});

it('counts a capture that could not be sorted as unsorted, not as still sorting', function (): void {
    $user = User::factory()->create();
    Capture::factory()->for($user)->create(['failed_at' => now()]);
    Capture::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('home.unsortedCount', 1)
            ->where('home.sortingCount', 1)
            ->where('home.aiConsented', true)
        );
});

it('answers the same count on home and when overwhelmed', function (): void {
    $user = User::factory()->create();
    kitchen(user: $user);
    kitchen(user: $user);
    kitchen(user: $user);
    WaitingFor::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->whereNot('home.rightNow', null)
            ->where('home.restCount', 3));

    $this->actingAs($user)
        ->getJson('/api/v1/overwhelmed')
        ->assertOk()
        ->assertJsonPath('restCount', 3);
});

it('counts open commitments beyond the one on show', function (): void {
    $user = User::factory()->create();
    Commitment::factory()->count(3)->for($user)->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('home.needsAttention', 1)
            ->where('home.restCount', 2));
});

it('offers what was just finished so it can be set to repeat, and lets it go after an hour', function (): void {
    $user = User::factory()->create();
    $finished = Intention::factory()->for($user)->done()->create(['title' => 'Water the plants', 'completed_at' => now()->subMinutes(20)]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('home.justFinished.id', $finished->id)
            ->where('home.justFinished.recurrenceEveryDays', null));

    $this->actingAs($user)->post(route('intentions.recurrence', $finished), ['every_days' => 7]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('home.justFinished.recurrenceEveryDays', 7));

    $this->travel(2)->hours();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('home.justFinished', null));
});

it('sends focus back home when no session is open', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('focus'))
        ->assertRedirect(route('home'));
});

it('renders the step, its intention and the progress in focus', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->get(route('focus'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('focus')
            ->where('state.session.currentStep.title', 'Step 1.')
            ->where('state.intention.title', 'Clean the kitchen')
            ->where('state.progress.0', '0 of 3 steps done.')
        );
});

it('starts a session from home and lands in focus', function (): void {
    $intention = kitchen();
    $step = $intention->steps()->first();

    $this->actingAs($intention->user)
        ->post(route('focus.start'), ['step_id' => $step->id])
        ->assertRedirect(route('focus'));

    expect($intention->user->refresh())->not->toBeNull()
        ->and(ExecutionSession::query()->sole()->current_step_id)->toBe($step->id);
});

it('refuses to start on a step belonging to somebody else', function (): void {
    $intention = kitchen();

    $this->actingAs(User::factory()->create())
        ->post(route('focus.start'), ['step_id' => $intention->steps()->first()->id])
        ->assertNotFound();
});

it("hides another person's session behind the web controls too", function (): void {
    $session = started();

    $this->actingAs(User::factory()->create())
        ->post(route('focus.pause', $session))
        ->assertNotFound();
});

it('keeps the person on focus while the session is open', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->from(route('focus'))
        ->post(route('focus.complete-step', $session), ['step_id' => $session->current_step_id])
        ->assertRedirect(route('focus'));

    expect($session->refresh()->steps_completed)->toBe(1);
});

it('sends the person home once the session has ended', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->from(route('focus'))
        ->post(route('focus.stop', $session))
        ->assertRedirect(route('focus'));

    $this->actingAs($session->user)
        ->get(route('focus'))
        ->assertRedirect(route('home'));
});

it('starts a session from the API and finds it on home', function (): void {
    $session = started();

    StartSession::run($session->user, $session->intention->steps()->first());

    $this->actingAs($session->user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('home.session.session.id', $session->id));
});

it('asks the due check-in on home', function (): void {
    $this->actingAs(activeFor())
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('home.checkIn', 'overwhelm'));
});

it('holds the check-in back while a session is running', function (): void {
    $user = activeFor();
    StartSession::run($user, kitchen(user: $user)->steps()->first());

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('home.checkIn', null));
});

it('holds the check-in back while the reminder band shows', function (): void {
    $user = activeFor();
    CalendarEvent::factory()->for($user)->create(['starts_at' => CarbonImmutable::parse('2026-09-30 11:00:00')]);
    $this->travelTo(CarbonImmutable::parse('2026-09-30 10:02:00'));
    SendDueReminders::run($user);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->whereNot('home.reminder', null)
            ->where('home.checkIn', null)
        );
});
