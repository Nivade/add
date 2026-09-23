<?php

declare(strict_types=1);

use App\Actions\Sessions\AdvanceSession;
use App\Actions\Sessions\CompleteStep;
use App\Actions\Sessions\PauseSession;
use App\Actions\Sessions\RecordDistraction;
use App\Actions\Sessions\ResumeSession;
use App\Actions\Sessions\SkipCurrentStep;
use App\Actions\Sessions\StartSession;
use App\Actions\Sessions\StopSession;
use App\Data\ExecutionStateData;
use App\Enums\IntentionStatus;
use App\Enums\SessionOutcome;
use App\Enums\StepStatus;
use App\Models\ExecutionSession;
use App\Support\Execution\Exceptions\InvalidSessionTransition;
use Carbon\CarbonImmutable;

it('keeps one session for a stretch of work and moves it to the step they picked', function (): void {
    $session = started();
    $other = $session->intention->steps()->where('position', 3)->sole();

    $again = StartSession::run($session->user, $other);

    expect($again->id)->toBe($session->id)
        ->and($again->current_step_id)->toBe($other->id)
        ->and(ExecutionSession::query()->count())->toBe(1)
        ->and(replay($session))->toBe(['started', 'started']);
});

it('closes the stretch and opens another when the step belongs elsewhere', function (): void {
    $session = started();
    $elsewhere = kitchen()->steps()->first();
    $elsewhere->intention->update(['user_id' => $session->user_id]);

    $next = StartSession::run($session->user, $elsewhere);

    expect($next->id)->not->toBe($session->id)
        ->and($next->current_step_id)->toBe($elsewhere->id)
        ->and($session->refresh()->outcome)->toBe(SessionOutcome::Stopped)
        ->and($session->ended_at)->not->toBeNull();
});

it('advances to the next step when one is done', function (): void {
    $session = started();
    $first = $session->currentStep()->sole();

    CompleteStep::run($session);

    expect($first->refresh()->status)->toBe(StepStatus::Done)
        ->and($first->completed_at)->not->toBeNull()
        ->and($session->refresh()->steps_completed)->toBe(1)
        ->and($session->currentStep()->sole()->position)->toBe(2)
        ->and(replay($session))->toBe(['started', 'step_completed']);
});

it('completes the session and the intention when the last step is done', function (): void {
    $session = started(1);

    CompleteStep::run($session);

    expect($session->refresh()->outcome)->toBe(SessionOutcome::Completed)
        ->and($session->ended_at)->not->toBeNull()
        ->and($session->current_step_id)->toBeNull()
        ->and($session->intention->refresh()->status)->toBe(IntentionStatus::Done)
        ->and($session->intention->completed_at)->not->toBeNull();
});

it('moves past a skipped step and leaves it pending', function (): void {
    $session = started();
    $first = $session->currentStep()->sole();

    SkipCurrentStep::run($session);

    expect($first->refresh()->status)->toBe(StepStatus::Pending)
        ->and($first->skip_count)->toBe(1)
        ->and($first->last_skipped_at)->not->toBeNull()
        ->and($session->refresh()->current_step_id)->not->toBe($first->id)
        ->and($session->steps_completed)->toBe(0);
});

it('ends the session as continued when the only step left is the one just skipped', function (): void {
    $session = started(1);

    SkipCurrentStep::run($session);

    expect($session->refresh()->outcome)->toBe(SessionOutcome::Continued)
        ->and($session->intention->refresh()->status)->toBe(IntentionStatus::Active)
        ->and($session->intention->remainingSteps()->count())->toBe(1);
});

it('wraps to the front rather than stranding the steps after a skip', function (): void {
    $session = started(2);

    SkipCurrentStep::run($session);
    CompleteStep::run($session->refresh());

    expect($session->refresh()->currentStep()->sole()->position)->toBe(1);
});

it('pauses without writing an outcome and resumes the same session a day later', function (): void {
    $session = started();
    $step = $session->current_step_id;

    PauseSession::run($session);

    expect($session->refresh()->paused_at)->not->toBeNull()
        ->and($session->outcome)->toBeNull()
        ->and($session->ended_at)->toBeNull();

    $this->travelTo(CarbonImmutable::now()->addDay());

    ResumeSession::run($session);

    expect($session->refresh()->paused_at)->toBeNull()
        ->and($session->current_step_id)->toBe($step)
        ->and(ExecutionSession::query()->count())->toBe(1)
        ->and(replay($session))->toBe(['started', 'paused', 'resumed']);
});

it('refuses a transition the session cannot make', function (): void {
    $session = started();

    PauseSession::run($session);

    expect(fn () => PauseSession::run($session->refresh()))->toThrow(InvalidSessionTransition::class);

    ResumeSession::run($session->refresh());

    expect(fn () => ResumeSession::run($session->refresh()))->toThrow(InvalidSessionTransition::class);

    StopSession::run($session->refresh());

    expect(fn () => StopSession::run($session->refresh()))->toThrow(InvalidSessionTransition::class)
        ->and(fn () => CompleteStep::run($session->refresh()))->toThrow(InvalidSessionTransition::class)
        ->and(fn () => SkipCurrentStep::run($session->refresh()))->toThrow(InvalidSessionTransition::class)
        ->and(fn () => RecordDistraction::run($session->refresh()))->toThrow(InvalidSessionTransition::class);
});

it('refuses to land a session a second time even when advanced directly', function (): void {
    $session = started();

    StopSession::run($session);

    expect(fn () => AdvanceSession::run($session->refresh()))->toThrow(InvalidSessionTransition::class)
        ->and(replay($session))->toBe(['started', 'stopped']);
});

it('records a distraction without ending or scoring anything', function (): void {
    $session = started();

    RecordDistraction::run($session);

    expect($session->refresh()->ended_at)->toBeNull()
        ->and($session->outcome)->toBeNull()
        ->and($session->steps_completed)->toBe(0)
        ->and(replay($session))->toBe(['started', 'distracted']);
});

it('stops as a real answer rather than a failure', function (): void {
    $session = started();

    StopSession::run($session);

    expect($session->refresh()->outcome)->toBe(SessionOutcome::Stopped)
        ->and($session->intention->refresh()->status)->toBe(IntentionStatus::Active)
        ->and(replay($session))->toBe(['started', 'stopped']);
});

it('reconstructs the session from its events', function (): void {
    $session = started();

    CompleteStep::run($session);
    SkipCurrentStep::run($session->refresh());
    RecordDistraction::run($session->refresh());
    PauseSession::run($session->refresh());
    ResumeSession::run($session->refresh());
    StopSession::run($session->refresh());

    expect(replay($session))->toBe([
        'started', 'step_completed', 'step_skipped', 'distracted', 'paused', 'resumed', 'stopped',
    ]);
});

it('counts progress rather than writing it', function (): void {
    $session = started();

    CompleteStep::run($session);

    expect(ExecutionStateData::of($session->refresh())->progress)
        ->toBe(['1 of 3 steps done.', '1 step done in this sitting.', '1 thing finished today.']);
});
