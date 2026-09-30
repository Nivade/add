<?php

declare(strict_types=1);

use App\Enums\Place;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use Carbon\CarbonImmutable;

/** At home by the evidence, with a longer home step and a shorter out step waiting. */
function homeWithAnErrandWaiting(): User
{
    $user = User::factory()->create();

    $kitchen = Intention::factory()->decomposed()->for($user)->create();
    Step::factory()->for($kitchen)->create(['title' => 'Wipe one worktop.', 'position' => 1, 'place' => Place::Home, 'estimated_seconds' => 120]);

    $errands = Intention::factory()->decomposed()->for($user)->create();
    Step::factory()->for($errands)->create(['title' => 'Buy bin bags.', 'position' => 1, 'place' => Place::Out, 'estimated_seconds' => 600]);

    finishStepAt($user, Place::Home);

    return $user;
}

it('takes the correction from the web and re-ranks what comes next', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-26 10:00:00'));
    $user = homeWithAnErrandWaiting();

    $this->actingAs($user)->getJson('/api/v1/next-action')
        ->assertJsonPath('step.title', 'Wipe one worktop.')
        ->assertJsonPath('assumedPlace', 'home');

    $this->actingAs($user)->from('/home')
        ->post('/whereabouts/not-here', ['place' => 'home'])
        ->assertRedirect('/home');

    expect($user->notHereReports()->sole()->place)->toBe(Place::Home);

    $this->actingAs($user)->getJson('/api/v1/next-action')
        ->assertJsonPath('step.title', 'Buy bin bags.')
        ->assertJsonPath('assumedPlace', null);
});

it('takes the correction from the phone and re-ranks what comes next', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-26 10:00:00'));
    $user = homeWithAnErrandWaiting();

    $this->actingAs($user)
        ->postJson('/api/v1/whereabouts/not-here', ['place' => 'home'])
        ->assertNoContent();

    expect($user->notHereReports()->sole()->place)->toBe(Place::Home);

    $this->actingAs($user)->getJson('/api/v1/next-action')
        ->assertJsonPath('step.title', 'Buy bin bags.');
});

it('refuses a place that does not exist', function (mixed $place): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/whereabouts/not-here', ['place' => $place])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('place');

    expect($user->notHereReports()->count())->toBe(0);
})->with(['garden', null, '']);

it('refuses an unauthenticated correction', function (): void {
    $this->postJson('/api/v1/whereabouts/not-here', ['place' => 'home'])->assertUnauthorized();
    $this->post('/whereabouts/not-here', ['place' => 'home'])->assertRedirect('/login');
});
