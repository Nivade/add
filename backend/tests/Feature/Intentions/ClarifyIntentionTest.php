<?php

declare(strict_types=1);

use App\Models\Capture;
use App\Models\Intention;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('holds an undecided thought in needs attention until it is answered, then offers its first step', function (): void {
    $provider = fakeAi()->push(parsedCapture([
        'title' => 'Renew my passport',
        'clarifying_question' => 'Is there a trip you need it for, and when?',
    ]));
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('captures.store'), ['body' => 'I should probably renew my passport'])
        ->assertRedirect(route('home'));

    $intention = Intention::query()->sole();

    expect($intention->decomposed_at)->toBeNull();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('home.rightNow', null)
            ->where('home.needsAttention.0.clarifyingQuestion', 'Is there a trip you need it for, and when?')
        );

    $provider->push(['steps' => [['title' => 'Find the old passport.', 'estimated_seconds' => 240]]]);

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('intentions.clarification', $intention), ['answer' => 'Lisbon in March'])
        ->assertRedirect(route('home'));

    expect($provider->received[1]->user)->toContain('They answered: Lisbon in March')
        ->and(Capture::query()->sole()->intention_id)->toBe($intention->id)
        ->and(Intention::query()->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('home.rightNow.step.title', 'Find the old passport.')
            ->has('home.needsAttention', 0)
        );
});

it('answers over the API too', function (): void {
    fakeAi()->push(['steps' => [['title' => 'Find the old passport.', 'estimated_seconds' => 240]]]);
    $user = User::factory()->create();
    $intention = Intention::factory()->unclear()->for($user)->create();

    $this->actingAs($user)
        ->patchJson(route('api.v1.intentions.clarification', $intention), ['answer' => 'The drawer one'])
        ->assertOk()
        ->assertJsonPath('id', $intention->id);

    expect($intention->refresh()->needs_clarification)->toBeFalse()
        ->and($intention->clarification)->toBe('The drawer one');
});

it('does not let one person answer for another', function (): void {
    $intention = Intention::factory()->unclear()->create();

    $this->actingAs(User::factory()->create())
        ->patchJson(route('api.v1.intentions.clarification', $intention), ['answer' => 'The drawer one'])
        ->assertNotFound();

    expect($intention->refresh()->needs_clarification)->toBeTrue();
});

it('refuses an empty answer and a second one', function (): void {
    $user = User::factory()->create();
    $unclear = Intention::factory()->unclear()->for($user)->create();
    $clear = Intention::factory()->for($user)->create();

    $this->actingAs($user)
        ->patchJson(route('api.v1.intentions.clarification', $unclear), ['answer' => ''])
        ->assertJsonValidationErrors('answer');

    $this->actingAs($user)
        ->patchJson(route('api.v1.intentions.clarification', $clear), ['answer' => 'Again'])
        ->assertJsonValidationErrors('answer');

    expect($clear->refresh()->clarification)->toBeNull();
});
