<?php

declare(strict_types=1);

use App\Actions\Sessions\ReportStuck;
use App\Actions\Steps\SkipStep;
use App\Actions\Steps\SplitStep;
use App\Enums\SessionOutcome;
use App\Enums\StepStatus;
use App\Enums\StuckReason;
use App\Models\ExecutionSession;
use App\Models\Step;
use App\Support\Ai\Providers\FakeAiProvider;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Decorators\JobDecorator;

function answeredSplit(array $steps): FakeAiProvider
{
    return fakeAi()->push(['steps' => $steps]);
}

it('moves to the shortest sibling and queues the split when the step is too big', function (): void {
    Queue::fake();

    $session = started();
    $big = $session->currentStep()->sole();

    ReportStuck::run($session, StuckReason::TooBig);

    expect($session->refresh()->currentStep()->sole()->position)->toBe(2)
        ->and($session->ended_at)->toBeNull();

    Queue::assertPushed(JobDecorator::class, fn (JobDecorator $job): bool => $job->getAction() instanceof SplitStep
        && $job->getParameters()[0]->id === $big->id);
});

it('trades a step too big for the shortest one, not for one nobody estimated', function (): void {
    Queue::fake();

    $session = started();

    $session->intention->steps()->where('position', 2)->sole()->update(['estimated_seconds' => null]);

    ReportStuck::run($session, StuckReason::TooBig);

    // Position 3 is the shortest of what is left once the unestimated one stops sorting first.
    expect($session->refresh()->currentStep()->sole()->position)->toBe(3);
});

it('does not hand back a step that was skipped minutes ago', function (): void {
    Queue::fake();

    $session = started();
    $shortest = $session->intention->steps()->where('position', 2)->sole();

    SkipStep::run($shortest);

    ReportStuck::run($session, StuckReason::TooBig);

    expect($session->refresh()->current_step_id)->not->toBe($shortest->id);
});

it('records the blocker and moves to another step when something is missing', function (): void {
    Queue::fake();

    $session = started();
    $blocked = $session->current_step_id;

    ReportStuck::run($session, StuckReason::NeedSomething, 'The drill is at my mother-in-law.');

    $event = $session->events()->where('type', 'stuck')->sole();

    expect($event->payload)->toBe(['reason' => 'need_something', 'note' => 'The drill is at my mother-in-law.'])
        ->and($event->step_id)->toBe($blocked)
        ->and($session->refresh()->current_step_id)->not->toBe($blocked);
});

it('ends the session cleanly when the answer is tired or unwilling', function (StuckReason $reason): void {
    $session = started();

    ReportStuck::run($session, $reason);

    expect($session->refresh()->outcome)->toBe(SessionOutcome::Stopped)
        ->and(replay($session))->toBe(['started', 'stuck', 'stopped']);
})->with([StuckReason::Tired, StuckReason::DontWantTo]);

it('stores free text and stays where it was for anything else', function (): void {
    $session = started();
    $step = $session->current_step_id;

    ReportStuck::run($session, StuckReason::SomethingElse, 'The cat is on the keyboard.');

    expect($session->refresh()->current_step_id)->toBe($step)
        ->and($session->ended_at)->toBeNull()
        ->and($session->events()->where('type', 'stuck')->sole()->payload['note'])
        ->toBe('The cat is on the keyboard.');
});

it('leaves every stuck answer with something to start or a clean stop', function (StuckReason $reason): void {
    Queue::fake();

    $session = started();

    ReportStuck::run($session, $reason);

    $session->refresh();

    expect($session->current_step_id !== null || $session->ended_at !== null)->toBeTrue();
})->with(StuckReason::cases());

it('replaces the step it was asked to split and keeps the session pointing at work', function (): void {
    $session = started();
    $big = $session->currentStep()->sole();

    answeredSplit([
        ['title' => 'Pick up one thing.', 'estimated_seconds' => 20],
        ['title' => 'Put it where it belongs.', 'estimated_seconds' => 40],
    ]);

    SplitStep::run($big);

    $steps = $session->intention->steps()->get();

    expect(Step::query()->find($big->id))->toBeNull()
        ->and($steps->pluck('title')->all())->toBe([
            'Pick up one thing.',
            'Put it where it belongs.',
            'Step 2.',
            'Step 3.',
        ])
        ->and($steps->pluck('position')->all())->toBe([1, 2, 3, 4])
        ->and($session->refresh()->currentStep()->sole()->title)->toBe('Pick up one thing.');
});

it('leaves a step alone when the person finished it before the split ran', function (): void {
    $session = started();
    $step = $session->currentStep()->sole();

    $step->update(['status' => StepStatus::Done]);

    expect(SplitStep::run($step))->toBeEmpty()
        ->and(Step::query()->find($step->id))->not->toBeNull();
});

it('does not open a second session while splitting', function (): void {
    Queue::fake();

    $session = started();

    ReportStuck::run($session, StuckReason::DontKnowWhatToDo);

    expect(ExecutionSession::query()->count())->toBe(1);
});

it('leaves the replaced step in the history the split writes', function (): void {
    $session = started();
    $big = $session->currentStep()->sole();

    SkipStep::run($big);

    answeredSplit([
        ['title' => 'Pick up one thing.', 'estimated_seconds' => 20],
        ['title' => 'Put it where it belongs.', 'estimated_seconds' => 40],
    ]);

    SplitStep::run($big->refresh());

    $event = $session->events()->where('type', 'step_split')->sole();

    expect($event->step_id)->toBe($big->id)
        ->and($event->payload['title'])->toBe('Step 1.')
        ->and($event->payload['skip_count'])->toBe(1)
        ->and($event->payload['replaced_by'])->toHaveCount(2)
        ->and(replay($session))->toBe(['started', 'step_split']);
});

it('advances rather than leaving them on the step they called too big', function (): void {
    Queue::fake();

    $session = started(1);
    $only = $session->currentStep()->sole();

    ReportStuck::run($session, StuckReason::TooBig);

    expect($session->refresh()->current_step_id)->not->toBe($only->id)
        ->and($session->outcome)->toBe(SessionOutcome::Continued);
});
