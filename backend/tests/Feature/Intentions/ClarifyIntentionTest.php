<?php

declare(strict_types=1);

use App\Contracts\NextActionResolver;
use App\Models\Capture;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use Carbon\CarbonImmutable;
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
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
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
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
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
        ->and($intention->clarification)->toBe('The drawer one')
        ->and($intention->deadline_at)->toBeNull();
});

it('reads a date in the answer as a deadline to confirm, and ranks on it', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-16 10:00:00', 'Europe/Amsterdam'));
    fakeAi()->push(['steps' => [['title' => 'Find the old passport.', 'estimated_seconds' => 240]]]);
    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);
    $passport = Intention::factory()->unclear('Is there a trip you need it for, and when?')->for($user)->create();
    $socks = Intention::factory()->decomposed()->for($user)->create(['created_at' => now()->subWeek()]);
    Step::factory()->for($socks)->create(['title' => 'Sort the socks.', 'position' => 1, 'estimated_seconds' => 240]);

    $this->actingAs($user)
        ->patchJson(route('api.v1.intentions.clarification', $passport), ['answer' => 'Lisbon in March'])
        ->assertOk()
        ->assertJsonPath('deadlineInferred', true);

    $answer = app(NextActionResolver::class)->resolve($user, ResolutionContext::forUser($user));

    expect($passport->refresh()->deadline_at?->setTimezone('Europe/Amsterdam')->toDateTimeString())->toBe('2027-03-01 00:00:00')
        ->and($passport->deadline_confirmed_at)->toBeNull()
        ->and($answer?->step->title)->toBe('Find the old passport.')
        ->and($answer?->why)->toBe(['Your deadline is 5 months from now.', 'This takes about 4 minutes.']);
});

it('never lets an answer move a date the person already gave', function (): void {
    fakeAi()->push(['steps' => [['title' => 'Find the old passport.', 'estimated_seconds' => 240]]]);
    $user = User::factory()->create();
    $deadline = CarbonImmutable::parse('2026-12-01 12:00:00');
    $intention = Intention::factory()->unclear()->for($user)->create(['deadline_at' => $deadline]);

    $this->actingAs($user)
        ->patchJson(route('api.v1.intentions.clarification', $intention), ['answer' => 'Lisbon in March'])
        ->assertOk();

    expect($intention->refresh()->deadline_at?->equalTo($deadline))->toBeTrue();
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
