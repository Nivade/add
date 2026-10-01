<?php

declare(strict_types=1);

use App\Actions\Sessions\CompleteStep;
use App\Actions\Sessions\RecordDistraction;
use App\Actions\Sessions\StopSession;
use App\Enums\IntentionStatus;
use App\Enums\StepStatus;
use App\Enums\StuckReason;
use App\Models\ExecutionSession;
use App\Models\User;
use Illuminate\Support\Facades\Exceptions;
use Inertia\Testing\AssertableInertia;

it('answers a null-shaped 200 when no session is open', function (): void {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/v1/sessions/current')
        ->assertOk()
        ->assertContent('null');
});

it('starts a session on a step and returns the state to render', function (): void {
    $intention = kitchen();
    $step = $intention->steps()->first();

    $this->actingAs($intention->user)
        ->postJson('/api/v1/sessions', ['step_id' => $step->id])
        ->assertCreated()
        ->assertJsonPath('session.currentStep.id', $step->id)
        ->assertJsonPath('intention.title', 'Clean the kitchen')
        ->assertJsonPath('progress.0', '0 of 3 steps done.');
});

it('moves the running session to the step they asked for rather than creating a second one', function (): void {
    $session = started();
    $third = $session->intention->steps()->where('position', 3)->sole();

    $this->actingAs($session->user)
        ->postJson('/api/v1/sessions', ['step_id' => $third->id])
        ->assertOk()
        ->assertJsonPath('session.id', $session->id)
        ->assertJsonPath('session.currentStep.id', $third->id);
});

it('refuses to start on work that is already behind them', function (): void {
    $intention = kitchen();
    $step = $intention->steps()->first();
    $step->update(['status' => StepStatus::Done]);

    $this->actingAs($intention->user)
        ->postJson('/api/v1/sessions', ['step_id' => $step->id])
        ->assertNotFound();

    $second = $intention->steps()->where('position', 2)->sole();
    $intention->update(['status' => IntentionStatus::Done]);

    $this->actingAs($intention->user)
        ->postJson('/api/v1/sessions', ['step_id' => $second->id])
        ->assertNotFound();
});

it('answers 200 for each control that mutates an open session', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/complete-step", ['step_id' => $session->current_step_id])
        ->assertOk()
        ->assertJsonPath('session.stepsCompleted', 1);

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/skip-step", ['step_id' => $session->refresh()->current_step_id])
        ->assertOk();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/distracted", ['seen_event_id' => seenEvent($session)])
        ->assertOk()
        ->assertJsonPath('session.endedAt', null)
        ->assertJsonPath('returning', true);

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/resume", ['seen_event_id' => seenEvent($session)])
        ->assertOk()
        ->assertJsonPath('returning', false);

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/pause", ['seen_event_id' => seenEvent($session)])
        ->assertOk();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/resume", ['seen_event_id' => seenEvent($session)])
        ->assertOk();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/stop", ['seen_event_id' => seenEvent($session)])
        ->assertOk()
        ->assertJsonPath('session.outcome', 'stopped');
});

it('reads the stuck reason and refuses one it does not know', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/stuck", ['step_id' => $session->current_step_id, 'reason' => 'tired'])
        ->assertOk()
        ->assertJsonPath('session.outcome', 'stopped');

    $other = started();

    $this->actingAs($other->user)
        ->postJson("/api/v1/sessions/{$other->id}/stuck", ['step_id' => $other->current_step_id, 'reason' => 'cannot be bothered'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('reason');
});

it('hides a session that belongs to somebody else', function (): void {
    $session = started();

    $this->actingAs(User::factory()->create())
        ->postJson("/api/v1/sessions/{$session->id}/pause", ['seen_event_id' => seenEvent($session)])
        ->assertNotFound();
});

it("refuses to start a session on somebody else's step", function (): void {
    $intention = kitchen();

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/sessions', ['step_id' => $intention->steps()->first()->id])
        ->assertNotFound();
});

it('refuses an unauthenticated reader', function (): void {
    $this->getJson('/api/v1/sessions/current')->assertUnauthorized();
});

it('reports an ended session as a conflict rather than a crash', function (): void {
    Exceptions::fake();
    $session = started();

    StopSession::run($session);

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/pause", ['seen_event_id' => seenEvent($session)])
        ->assertStatus(409);

    Exceptions::assertNothingReported();
});

it('answers a conflict when a double tap finishes a step the session has moved past', function (): void {
    Exceptions::fake();
    $session = started();
    $first = $session->current_step_id;

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/complete-step", ['step_id' => $first])
        ->assertOk();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/complete-step", ['step_id' => $first])
        ->assertStatus(409);

    expect($session->refresh()->steps_completed)->toBe(1)
        ->and($session->currentStep()->sole()->status)->toBe(StepStatus::Pending);

    Exceptions::assertNothingReported();
});

it('answers the API with a conflict even when the client did not ask for JSON', function (): void {
    $session = started();
    $first = $session->current_step_id;
    CompleteStep::run($session, $first);

    $this->actingAs($session->user)
        ->post("/api/v1/sessions/{$session->id}/complete-step", ['step_id' => $first])
        ->assertStatus(409);
});

it('sends a stale web control back without changing anything', function (): void {
    $session = started();
    $first = $session->current_step_id;

    $this->actingAs($session->user)
        ->from(route('focus'))
        ->post(route('focus.complete-step', $session), ['step_id' => $first])
        ->assertRedirect(route('focus'));

    $this->actingAs($session->user)
        ->from(route('focus'))
        ->post(route('focus.skip-step', $session), ['step_id' => $first])
        ->assertRedirect(route('focus'));

    expect($session->refresh()->steps_completed)->toBe(1)
        ->and($session->currentStep()->sole()->skip_count)->toBe(0)
        ->and(replay($session))->toBe(['started', 'step_completed']);
});

it('refuses a step control that does not say which step it means', function (string $control, array $body): void {
    $session = started();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/{$control}", $body)
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('step_id');

    expect(replay($session))->toBe(['started']);
})->with([
    'complete' => ['complete-step', []],
    'skip' => ['skip-step', []],
    'stuck' => ['stuck', ['reason' => 'tired']],
]);

it('carries the stuck note through the endpoint', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/stuck", [
            'step_id' => $session->current_step_id,
            'reason' => StuckReason::NeedSomething->value,
            'note' => 'The drill is at my mother-in-law.',
        ])
        ->assertOk();

    expect($session->events()->where('type', 'stuck')->sole()->payload['note'])
        ->toBe('The drill is at my mother-in-law.');
});

it('resolves the same running session on every screen when two are open', function (): void {
    $session = started();

    // A second row can only come from a race, and every reader has to pick the same one anyway.
    $later = ExecutionSession::factory()
        ->for($session->user)
        ->for($session->intention)
        ->create([
            'current_step_id' => $session->intention->steps()->where('position', 2)->sole()->id,
            'started_at' => $session->started_at->addMinutes(5),
        ]);

    $this->actingAs($session->user)
        ->getJson('/api/v1/sessions/current')
        ->assertOk()
        ->assertJsonPath('session.id', $later->id);

    $this->actingAs($session->user)
        ->get(route('focus'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('state.session.id', $later->id));

    $this->actingAs($session->user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('home.session.session.id', $later->id));
});

it('opens one session when start is pressed twice', function (): void {
    $intention = kitchen();
    $step = $intention->steps()->first();

    foreach ([1, 2] as $press) {
        $this->actingAs($intention->user)->postJson('/api/v1/sessions', ['step_id' => $step->id]);
    }

    expect(ExecutionSession::query()->where('user_id', $intention->user->id)->count())->toBe(1);
});

it('refuses a second tap on a session control once the session has moved on', function (string $control, string $first): void {
    Exceptions::fake();
    $session = started();
    $stale = seenEvent($session);

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/{$first}", ['seen_event_id' => $stale])
        ->assertOk();

    $events = $session->events()->count();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/{$control}", ['seen_event_id' => $stale])
        ->assertConflict();

    $this->actingAs($session->user)
        ->from(route('focus'))
        ->post(route("focus.{$control}", $session), ['seen_event_id' => $stale])
        ->assertRedirect(route('focus'));

    expect($session->events()->count())->toBe($events)
        ->and($session->refresh()->ended_at)->toBeNull();

    Exceptions::assertNothingReported();
})->with([
    'pause after resume' => ['pause', 'resume'],
    'stop after distracted' => ['stop', 'distracted'],
    'distracted after pause' => ['distracted', 'pause'],
]);

it('asks every session control which moment it was tapped against', function (string $control): void {
    $session = started();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/{$control}")
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('seen_event_id');
})->with(['pause', 'resume', 'distracted', 'stop']);

it('still stops when a stuck answer says to, with no tap to compare against', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/stuck", ['step_id' => $session->current_step_id, 'reason' => 'dont_want_to'])
        ->assertOk()
        ->assertJsonPath('session.outcome', 'stopped');
});

it('resumes from home and lands on focus, once', function (): void {
    $session = started();
    RecordDistraction::run($session);

    $this->actingAs($session->user)
        ->from(route('home'))
        ->post(route('focus.resume', $session), ['seen_event_id' => seenEvent($session)])
        ->assertRedirect(route('focus'));

    $this->actingAs($session->user)
        ->get(route('focus'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('state.returning', false));
});
