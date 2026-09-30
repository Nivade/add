<?php

declare(strict_types=1);

use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Nvade\AiToolkit\Testing\FakeAiProvider;

function answeredCapture(): FakeAiProvider
{
    return fakeAi()
        ->respondWith(parsedCapture(['title' => 'Clean the kitchen', 'why' => 'Parents are coming']))
        ->respondWith(['steps' => [
            ['title' => 'Grab a bin bag.', 'estimated_seconds' => 60],
            ['title' => 'Put the obvious rubbish in the bag.', 'estimated_seconds' => 300],
        ]]);
}

it('carries one typed thought all the way to a finished intention', function (): void {
    answeredCapture();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('captures.store'), ['body' => 'the kitchen is a state and my parents are coming'])
        ->assertRedirect(route('home'));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('home.rightNow.step.title', 'Grab a bin bag.')
            ->where('home.rightNow.intention.why', 'Parents are coming')
        );

    $step = Intention::query()->sole()->steps()->first();

    $this->actingAs($user)
        ->post(route('focus.start'), ['step_id' => $step->id])
        ->assertRedirect(route('focus'));

    $session = ExecutionSession::query()->sole();

    $this->actingAs($user)
        ->from(route('focus'))
        ->post(route('focus.complete-step', $session), ['step_id' => $session->refresh()->current_step_id])
        ->assertRedirect(route('focus'));

    $this->actingAs($user)
        ->get(route('focus'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('state.session.currentStep.title', 'Put the obvious rubbish in the bag.')
            ->where('state.progress.0', '1 of 2 steps done.')
        );

    $this->actingAs($user)
        ->from(route('focus'))
        ->post(route('focus.distracted', $session))
        ->assertRedirect(route('focus'));

    $this->actingAs($user)
        ->get(route('focus'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('state.returning', true));

    $this->actingAs($user)
        ->from(route('focus'))
        ->post(route('focus.resume', $session))
        ->assertRedirect(route('focus'));

    $this->actingAs($user)
        ->from(route('focus'))
        ->post(route('focus.pause', $session))
        ->assertRedirect(route('focus'));

    $this->actingAs($user)
        ->get(route('focus'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('state.session.pausedAt', fn (?string $at): bool => $at !== null));

    $this->actingAs($user)
        ->from(route('focus'))
        ->post(route('focus.resume', $session))
        ->assertRedirect(route('focus'));

    $this->actingAs($user)
        ->from(route('focus'))
        ->post(route('focus.complete-step', $session), ['step_id' => $session->refresh()->current_step_id])
        ->assertRedirect(route('focus'));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('home.rightNow', null)
            ->where('home.session', null)
            ->where('home.restCount', 0)
        );

    expect(Intention::query()->sole()->status->value)->toBe('done')
        ->and($session->refresh()->outcome?->value)->toBe('completed')
        ->and(replay($session))->toBe([
            'started', 'step_completed', 'distracted', 'resumed', 'paused', 'resumed', 'step_completed', 'stopped',
        ]);
});
