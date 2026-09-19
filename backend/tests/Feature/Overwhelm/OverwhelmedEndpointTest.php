<?php

declare(strict_types=1);

use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('answers with a null step rather than an error when there is nothing to do', function (): void {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/v1/overwhelmed')
        ->assertOk()
        ->assertJsonPath('smallestStep', null)
        ->assertJsonPath('restCount', 0);
});

it('returns one step, its why and the count of everything else', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create(['title' => 'Clear the garage']);
    $small = Step::factory()->for($intention)->create(['title' => 'Empty the first shelf.', 'position' => 1, 'estimated_seconds' => 300]);
    Step::factory()->for($intention)->create(['title' => 'Hire a skip.', 'position' => 2, 'estimated_seconds' => 1800]);

    $this->actingAs($user)
        ->getJson('/api/v1/overwhelmed')
        ->assertOk()
        ->assertJsonPath('smallestStep.step.id', $small->id)
        ->assertJsonPath('smallestStep.why.0', 'This takes about 5 minutes.')
        ->assertJsonPath('restCount', 1);
});

it('refuses an unauthenticated reader', function (): void {
    $this->getJson('/api/v1/overwhelmed')->assertUnauthorized();
});

it('shows the web screen one step and never a second', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create();
    Step::factory()->for($intention)->create(['title' => 'Empty the first shelf.', 'position' => 1, 'estimated_seconds' => 300]);
    Step::factory()->for($intention)->create(['title' => 'Hire a skip.', 'position' => 2, 'estimated_seconds' => 1800]);

    $this->actingAs($user)
        ->get(route('overwhelmed'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('overwhelmed')
            ->where('overwhelmed.smallestStep.step.title', 'Empty the first shelf.')
            ->where('overwhelmed.restCount', 1)
        );
});
