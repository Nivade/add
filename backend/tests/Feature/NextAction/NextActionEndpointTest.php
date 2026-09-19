<?php

declare(strict_types=1);

use App\Models\Intention;
use App\Models\Step;
use App\Models\User;

it('answers a null-shaped 200 when there is nothing to do', function (): void {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/v1/next-action')
        ->assertOk()
        ->assertContent('null');
});

it('returns the step, its intention and the why', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create(['title' => 'Clear the garage']);
    $step = Step::factory()->for($intention)->create(['title' => 'Empty the first shelf.', 'position' => 1]);

    $this->actingAs($user)
        ->getJson('/api/v1/next-action')
        ->assertOk()
        ->assertJsonPath('step.id', $step->id)
        ->assertJsonPath('intention.title', 'Clear the garage')
        ->assertJsonPath('why.0', 'This takes about 3 minutes.');
});

it('refuses an unauthenticated reader', function (): void {
    $this->getJson('/api/v1/next-action')->assertUnauthorized();
});
