<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

function dated(User $user): Intention
{
    $intention = Intention::factory()->decomposed()->for($user)->create([
        'title' => 'Renew the passport',
        'deadline_at' => CarbonImmutable::now()->addDays(2),
    ]);

    Step::factory()->for($intention)->create(['position' => 1]);

    return $intention;
}

it('says a deadline it read is inferred, and stops saying it once confirmed', function (): void {
    $user = User::factory()->create();
    $intention = dated($user);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('home.comingUp.inferred', true));

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('intentions.deadline', $intention))
        ->assertRedirect(route('home'));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('home.comingUp.inferred', false));
});

it('never calls a time the calendar stated inferred', function (): void {
    $user = User::factory()->create();

    CalendarEvent::factory()->for($user)->create([
        'title' => 'Dentist',
        'starts_at' => CarbonImmutable::now()->addDay(),
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('home.comingUp.kind', 'calendar_event')
            ->where('home.comingUp.inferred', false)
        );
});

it('confirms a deadline over the API too', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson('/api/v1/intentions/'.dated($user)->id.'/deadline')
        ->assertOk()
        ->assertJsonPath('deadlineInferred', false);
});

it('does not let one person confirm another person\'s deadline', function (): void {
    $intention = dated(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->patchJson('/api/v1/intentions/'.$intention->id.'/deadline')
        ->assertNotFound();

    expect($intention->refresh()->deadline_confirmed_at)->toBeNull();
});

it('marks a step the model wrote as suggested rather than as the person\'s own words', function (): void {
    $user = User::factory()->create();
    $intention = dated($user);

    $intention->steps()->first()->update(['generated' => true, 'title' => 'Find the old passport.']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('home.rightNow.step.title', 'Find the old passport.')
            ->where('home.rightNow.step.generated', true)
        );
});
