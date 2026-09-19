<?php

declare(strict_types=1);

use App\Actions\Overwhelm\ReduceToOneStep;
use App\Actions\Steps\SkipStep;
use App\Data\OverwhelmedData;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use Carbon\CarbonImmutable;

function overwhelmed(User $user, ?CarbonImmutable $now = null): OverwhelmedData
{
    return ReduceToOneStep::run($user, new ResolutionContext($now ?? CarbonImmutable::now($user->timezone)));
}

it('answers nothing when there is nothing to do', function (): void {
    $answer = overwhelmed(User::factory()->create());

    expect($answer->smallestStep)->toBeNull()
        ->and($answer->restCount)->toBe(0);
});

it('takes the shortest step over the most useful one, and counts the rest', function (): void {
    $user = User::factory()->create();
    $now = CarbonImmutable::parse('2026-09-19 09:00:00');

    $urgent = Intention::factory()->decomposed()->for($user)->create([
        'title' => 'Get the car through its inspection',
        'deadline_at' => $now->addHours(2),
    ]);
    Step::factory()->for($urgent)->create(['title' => 'Drive to the garage.', 'position' => 1, 'estimated_seconds' => 2400]);

    $small = Intention::factory()->decomposed()->for($user)->create(['title' => 'Clear the garage']);
    Step::factory()->for($small)->create(['title' => 'Empty the first shelf.', 'position' => 1, 'estimated_seconds' => 300]);

    $answer = overwhelmed($user, $now);

    expect($answer->smallestStep?->step->title)->toBe('Empty the first shelf.')
        ->and($answer->smallestStep?->why)->toBe([
            'This takes about 5 minutes.',
            'Nothing else left is shorter.',
        ])
        ->and($answer->restCount)->toBe(1);
});

it('prefers a timed step to an unestimated one of the same assumed cost', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create();

    Step::factory()->for($intention)->create(['title' => 'Nobody timed this.', 'position' => 1, 'estimated_seconds' => null]);
    Step::factory()->for($intention)->create(['title' => 'Fold the letter.', 'position' => 2, 'estimated_seconds' => 900]);

    expect(overwhelmed($user)->smallestStep?->step->title)->toBe('Fold the letter.');
});

it('says so when the smallest thing is the only thing', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create();
    Step::factory()->for($intention)->create(['position' => 1, 'estimated_seconds' => null]);

    $answer = overwhelmed($user);

    expect($answer->smallestStep?->why)->toBe([
        'Nobody has estimated this one.',
        'It is the only thing left.',
    ])->and($answer->restCount)->toBe(0);
});

it('leaves a just-skipped step cooling off rather than offering it back', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create();

    $skipped = Step::factory()->for($intention)->create(['title' => 'Ring the dentist.', 'position' => 1, 'estimated_seconds' => 120]);
    Step::factory()->for($intention)->create(['title' => 'Book the van.', 'position' => 2, 'estimated_seconds' => 600]);

    SkipStep::run($skipped);

    expect(overwhelmed($user)->smallestStep?->step->title)->toBe('Book the van.')
        ->and(overwhelmed($user, CarbonImmutable::now()->addHours(5))->smallestStep?->step->title)->toBe('Ring the dentist.');
});

it('leaves work that belongs to somebody else alone', function (): void {
    $user = User::factory()->create();
    $stranger = Intention::factory()->decomposed()->create();
    Step::factory()->for($stranger)->create(['position' => 1, 'estimated_seconds' => 60]);

    expect(overwhelmed($user)->smallestStep)->toBeNull();
});
