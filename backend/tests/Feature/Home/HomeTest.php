<?php

declare(strict_types=1);

use App\Actions\Sessions\StartSession;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

it('pitches the product to a guest and sends a signed-in person to their answer', function (): void {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('welcome'));

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
        ->assertInertia(fn (AssertableInertia $page) => $page
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
        ->assertInertia(fn (AssertableInertia $page) => $page
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
        ->assertInertia(fn (AssertableInertia $page) => $page
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
        ->assertInertia(fn (AssertableInertia $page) => $page
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
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('home.restCount', 6)
            ->has('home.needsAttention', 0)
        );
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
        ->assertInertia(fn (AssertableInertia $page) => $page
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

it('hides another person\'s session behind the web controls too', function (): void {
    $session = started();

    $this->actingAs(User::factory()->create())
        ->post(route('focus.pause', $session))
        ->assertNotFound();
});

it('keeps the person on focus while the session is open', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->from(route('focus'))
        ->post(route('focus.complete-step', $session))
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
        ->assertInertia(fn (AssertableInertia $page) => $page->where('home.session.session.id', $session->id));
});
