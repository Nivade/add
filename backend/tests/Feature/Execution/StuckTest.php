<?php

declare(strict_types=1);

use App\Actions\Sessions\ReportStuck;
use App\Actions\Sessions\StartSession;
use App\Actions\Steps\SkipStep;
use App\Actions\Steps\SplitStep;
use App\Enums\Place;
use App\Enums\SessionOutcome;
use App\Enums\StepStatus;
use App\Enums\StuckReason;
use App\Models\ExecutionSession;
use App\Models\Step;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Nvade\AiToolkit\Testing\FakeAiProvider;

function answeredSplit(array $steps): FakeAiProvider
{
    return fakeAi()->respondWith(['steps' => $steps]);
}

it('moves to the shortest sibling and queues the split when the step is too big', function (): void {
    Queue::fake();

    $session = started();
    $big = $session->currentStep()->sole();

    ReportStuck::run($session, $session->current_step_id, StuckReason::TooBig);

    expect($session->refresh()->currentStep()->sole()->position)->toBe(2)
        ->and($session->ended_at)->toBeNull();

    Queue::assertPushed(JobDecorator::class, fn (JobDecorator $job): bool => $job->getAction() instanceof SplitStep
        && $job->getParameters()[0]->id === $big->id);
});

it('trades a step too big for the shortest one, not for one nobody estimated', function (): void {
    Queue::fake();

    $session = started();

    $session->intention->steps()->where('position', 2)->sole()->update(['estimated_seconds' => null]);

    ReportStuck::run($session, $session->current_step_id, StuckReason::TooBig);

    // Position 3 is the shortest of what is left once the unestimated one stops sorting first.
    expect($session->refresh()->currentStep()->sole()->position)->toBe(3);
});

it('does not hand back a step that was skipped minutes ago', function (): void {
    Queue::fake();

    $session = started();
    $shortest = $session->intention->steps()->where('position', 2)->sole();

    SkipStep::run($shortest);

    ReportStuck::run($session, $session->current_step_id, StuckReason::TooBig);

    expect($session->refresh()->current_step_id)->not->toBe($shortest->id);
});

it('records the blocker and moves to another step when something is missing', function (): void {
    Queue::fake();

    $session = started();
    $blocked = $session->current_step_id;

    ReportStuck::run($session, $session->current_step_id, StuckReason::NeedSomething, 'The drill is at my mother-in-law.');

    $event = $session->events()->where('type', 'stuck')->sole();

    expect($event->payload)->toEqual(['reason' => 'need_something', 'note' => 'The drill is at my mother-in-law.'])
        ->and($event->step_id)->toBe($blocked)
        ->and($session->refresh()->current_step_id)->not->toBe($blocked);
});

it('ends the session cleanly when the answer is tired or unwilling', function (StuckReason $reason): void {
    $session = started();

    ReportStuck::run($session, $session->current_step_id, $reason);

    expect($session->refresh()->outcome)->toBe(SessionOutcome::Stopped)
        ->and(replay($session))->toBe(['started', 'stuck', 'stopped']);
})->with([StuckReason::Tired, StuckReason::DontWantTo]);

it('stores free text and stays where it was for anything else', function (): void {
    $session = started();
    $step = $session->current_step_id;

    ReportStuck::run($session, $session->current_step_id, StuckReason::SomethingElse, 'The cat is on the keyboard.');

    expect($session->refresh()->current_step_id)->toBe($step)
        ->and($session->ended_at)->toBeNull()
        ->and($session->events()->where('type', 'stuck')->sole()->payload['note'])
        ->toBe('The cat is on the keyboard.');
});

it('leaves every stuck answer with something to start or a clean stop', function (StuckReason $reason): void {
    Queue::fake();

    $session = started();

    ReportStuck::run($session, $session->current_step_id, $reason);

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

it('splits a step into pieces that happen where the step happens', function (): void {
    $session = started();
    $big = $session->currentStep()->sole();
    $big->update(['place' => Place::Home]);

    answeredSplit([
        ['title' => 'Pick up one thing.', 'estimated_seconds' => 20, 'place' => 'out'],
        ['title' => 'Put it where it belongs.', 'estimated_seconds' => 40, 'place' => null],
    ]);

    $pieces = SplitStep::run($big->refresh());

    expect($pieces->pluck('place')->all())->toBe([Place::Home, Place::Home]);
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

    ReportStuck::run($session, $session->current_step_id, StuckReason::DontKnowWhatToDo);

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

    ReportStuck::run($session, $session->current_step_id, StuckReason::TooBig);

    expect($session->refresh()->current_step_id)->not->toBe($only->id)
        ->and($session->outcome)->toBe(SessionOutcome::Continued);
});

/** @param  list<Place|null>  $places */
function startedWithPlaces(array $places, int $at = 1): ExecutionSession
{
    $intention = kitchen(count($places));

    foreach ($places as $index => $place) {
        $intention->steps()->where('position', $index + 1)->update(['place' => $place]);
    }

    return StartSession::run($intention->user, $intention->steps()->where('position', $at)->sole());
}

it('moves on to a step somewhere else when the person is not in the right place', function (): void {
    $session = startedWithPlaces([Place::Out, Place::Out, Place::Home]);
    $first = $session->currentStep()->sole();

    ReportStuck::run($session, $session->current_step_id, StuckReason::NotHere);

    expect($session->refresh()->currentStep()->sole()->position)->toBe(3)
        ->and($session->ended_at)->toBeNull()
        ->and($session->user->notHereReports()->sole()->place)->toBe(Place::Out)
        ->and($first->refresh()->skip_count)->toBe(0)
        ->and($first->status)->toBe(StepStatus::Pending);
});

it('moves onwards before wrapping to the front, like any other move on', function (): void {
    $onwards = startedWithPlaces([Place::Home, Place::Out, Place::Home], at: 2);

    ReportStuck::run($onwards, $onwards->current_step_id, StuckReason::NotHere);

    $wrapped = startedWithPlaces([Place::Home, Place::Out, Place::Out], at: 2);

    ReportStuck::run($wrapped, $wrapped->current_step_id, StuckReason::NotHere);

    expect($onwards->refresh()->currentStep()->sole()->position)->toBe(3)
        ->and($wrapped->refresh()->currentStep()->sole()->position)->toBe(1);
});

it('counts a step with no place as somewhere else', function (): void {
    $session = startedWithPlaces([Place::Out, Place::Out, null]);

    ReportStuck::run($session, $session->current_step_id, StuckReason::NotHere);

    expect($session->refresh()->currentStep()->sole()->position)->toBe(3);
});

it('lands the session as continued when every other step needs the same place', function (): void {
    $session = startedWithPlaces([Place::Out, Place::Out]);

    ReportStuck::run($session, $session->current_step_id, StuckReason::NotHere);

    expect($session->refresh()->outcome)->toBe(SessionOutcome::Continued)
        ->and($session->user->notHereReports()->count())->toBe(1);
});

it('just moves on from a step with no place, reporting nothing', function (): void {
    $session = started();
    $first = $session->currentStep()->sole();

    ReportStuck::run($session, $session->current_step_id, StuckReason::NotHere);

    expect($session->refresh()->current_step_id)->not->toBe($first->id)
        ->and($session->user->notHereReports()->count())->toBe(0)
        ->and($first->refresh()->skip_count)->toBe(0);
});
