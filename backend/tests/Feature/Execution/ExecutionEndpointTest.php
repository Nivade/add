<?php

declare(strict_types=1);

use App\Actions\Sessions\StopSession;
use App\Enums\StuckReason;
use App\Models\User;

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

it('returns the running session rather than creating a second one', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->postJson('/api/v1/sessions', ['step_id' => $session->intention->steps()->where('position', 3)->sole()->id])
        ->assertOk()
        ->assertJsonPath('session.id', $session->id);
});

it('answers 200 for each control that mutates an open session', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/complete-step")
        ->assertOk()
        ->assertJsonPath('session.stepsCompleted', 1);

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/skip-step")
        ->assertOk();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/distracted")
        ->assertOk()
        ->assertJsonPath('session.endedAt', null);

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/pause")
        ->assertOk();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/resume")
        ->assertOk();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/stop")
        ->assertOk()
        ->assertJsonPath('session.outcome', 'stopped');
});

it('reads the stuck reason and refuses one it does not know', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/stuck", ['reason' => 'tired'])
        ->assertOk()
        ->assertJsonPath('session.outcome', 'stopped');

    $other = started();

    $this->actingAs($other->user)
        ->postJson("/api/v1/sessions/{$other->id}/stuck", ['reason' => 'cannot be bothered'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('reason');
});

it('hides a session that belongs to somebody else', function (): void {
    $session = started();

    $this->actingAs(User::factory()->create())
        ->postJson("/api/v1/sessions/{$session->id}/pause")
        ->assertNotFound();
});

it('refuses to start a session on somebody else\'s step', function (): void {
    $intention = kitchen();

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/sessions', ['step_id' => $intention->steps()->first()->id])
        ->assertNotFound();
});

it('refuses an unauthenticated reader', function (): void {
    $this->getJson('/api/v1/sessions/current')->assertUnauthorized();
});

it('reports an ended session as a conflict rather than a crash', function (): void {
    $session = started();

    StopSession::run($session);

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/pause")
        ->assertStatus(409);
});

it('carries the stuck note through the endpoint', function (): void {
    $session = started();

    $this->actingAs($session->user)
        ->postJson("/api/v1/sessions/{$session->id}/stuck", [
            'reason' => StuckReason::NeedSomething->value,
            'note' => 'The drill is at my mother-in-law.',
        ])
        ->assertOk();

    expect($session->events()->where('type', 'stuck')->sole()->payload['note'])
        ->toBe('The drill is at my mother-in-law.');
});
