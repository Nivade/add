<?php

declare(strict_types=1);

use App\Actions\Steps\SkipStep;
use App\Contracts\NextActionResolver;
use App\Data\NextActionData;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use Carbon\CarbonImmutable;

function nextAction(User $user, ?CarbonImmutable $now = null): ?NextActionData
{
    return app(NextActionResolver::class)->resolve(
        $user,
        new ResolutionContext($now ?? CarbonImmutable::now($user->timezone))
    );
}

it('answers nothing when there is nothing to do', function (): void {
    expect(nextAction(User::factory()->create()))->toBeNull();
});

it('stays in the session rather than re-ranking mid-task', function (): void {
    $user = User::factory()->create();

    $started = Intention::factory()->decomposed()->for($user)->create();
    $current = Step::factory()->for($started)->create(['title' => 'Write the second paragraph.', 'position' => 2]);

    $urgent = Intention::factory()->decomposed()->for($user)->create([
        'deadline_at' => CarbonImmutable::now()->addHour(),
    ]);
    Step::factory()->for($urgent)->create(['title' => 'Call the garage.', 'position' => 1]);

    ExecutionSession::factory()->for($user)->for($started)->create(['current_step_id' => $current->id]);

    $answer = nextAction($user);

    expect($answer?->step->id)->toBe($current->id)
        ->and($answer?->why)->toBe(['You are part-way through this one.']);
});

it('picks the deadline whose remaining steps only just fit, and says so', function (): void {
    $user = User::factory()->create();
    $now = CarbonImmutable::parse('2026-09-19 09:00:00');

    $tight = Intention::factory()->decomposed()->for($user)->create([
        'title' => 'Get the car through its inspection',
        'deadline_at' => $now->addHours(2),
    ]);

    foreach ([1, 2, 3] as $position) {
        Step::factory()->for($tight)->create([
            'title' => "Inspection step {$position}.",
            'position' => $position,
            'estimated_seconds' => 2400,
        ]);
    }

    $roomy = Intention::factory()->decomposed()->for($user)->create(['title' => 'Clear the garage']);
    Step::factory()->for($roomy)->create(['title' => 'Empty the first shelf.', 'position' => 1, 'estimated_seconds' => 600]);

    $answer = nextAction($user, $now);

    expect($answer?->step->title)->toBe('Inspection step 1.')
        ->and($answer?->why)->toBe([
            'Your deadline is 2 hours from now and what is left only just fits.',
            'This takes about 40 minutes.',
        ]);
});

it('prefers a real deadline over none when both are comfortably far off', function (): void {
    $user = User::factory()->create();
    $now = CarbonImmutable::parse('2026-09-19 09:00:00');

    $dated = Intention::factory()->decomposed()->for($user)->create(['deadline_at' => $now->addDay()]);
    Step::factory()->for($dated)->create(['title' => 'Print the form.', 'position' => 1, 'estimated_seconds' => 300]);

    $undated = Intention::factory()->decomposed()->for($user)->create();
    Step::factory()->for($undated)->create(['title' => 'Sort the socks.', 'position' => 1, 'estimated_seconds' => 300]);

    $answer = nextAction($user, $now);

    expect($answer?->step->title)->toBe('Print the form.')
        ->and($answer?->why)->toBe([
            'Your deadline is 1 day from now.',
            'This takes about 5 minutes.',
        ]);
});

it('stops treating a deadline that has passed as a reach, and says it flatly', function (): void {
    $user = User::factory()->create();
    $now = CarbonImmutable::parse('2026-09-19 09:00:00');

    $passed = Intention::factory()->decomposed()->for($user)->create([
        'title' => 'Send the rental form',
        'deadline_at' => $now->subDays(2),
    ]);
    Step::factory()->for($passed)->create(['title' => 'Scan the form.', 'position' => 1, 'estimated_seconds' => 600]);

    $ahead = Intention::factory()->decomposed()->for($user)->create(['deadline_at' => $now->addHour()]);
    foreach ([1, 2] as $position) {
        Step::factory()->for($ahead)->create([
            'title' => "Inspection step {$position}.",
            'position' => $position,
            'estimated_seconds' => 2400,
        ]);
    }

    $answer = nextAction($user, $now);

    // The one still ahead wins on reach; the passed one keeps its place and its plain sentence.
    expect($answer?->step->title)->toBe('Inspection step 1.');

    $ahead->steps->each->delete();

    expect(nextAction($user, $now)?->why)->toBe([
        'Your deadline was 2 days ago.',
        'This takes about 10 minutes.',
    ]);
});

it('leads with the soonest deadline when several are ahead', function (): void {
    $user = User::factory()->create();
    $now = CarbonImmutable::parse('2026-09-19 09:00:00');

    $later = Intention::factory()->decomposed()->for($user)->create(['deadline_at' => $now->addDays(3)]);
    Step::factory()->for($later)->create(['title' => 'Book the van.', 'position' => 1, 'estimated_seconds' => 300]);

    $sooner = Intention::factory()->decomposed()->for($user)->create(['deadline_at' => $now->addDay()]);
    Step::factory()->for($sooner)->create(['title' => 'Print the form.', 'position' => 1, 'estimated_seconds' => 300]);

    expect(nextAction($user, $now)?->step->title)->toBe('Print the form.');
});

it('offers a prerequisite before the shorter step that follows it', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create();

    Step::factory()->for($intention)->create([
        'title' => 'Empty the top shelf.',
        'position' => 1,
        'estimated_seconds' => 900,
    ]);
    Step::factory()->for($intention)->create([
        'title' => 'Wipe the shelf.',
        'position' => 2,
        'estimated_seconds' => 60,
    ]);

    $answer = nextAction($user);

    expect($answer?->step->title)->toBe('Empty the top shelf.')
        ->and($answer?->why)->toBe([
            'It is the first thing left in this one.',
            'This takes about 15 minutes.',
        ]);
});

it('still prefers the shortest thing across two intentions', function (): void {
    $user = User::factory()->create();
    $now = CarbonImmutable::parse('2026-09-19 09:00:00');

    $long = Intention::factory()->decomposed()->for($user)->create(['created_at' => $now->subDays(2)]);
    Step::factory()->for($long)->create(['title' => 'Strip the wallpaper.', 'position' => 1, 'estimated_seconds' => 3600]);

    $short = Intention::factory()->decomposed()->for($user)->create(['created_at' => $now->subDay()]);
    Step::factory()->for($short)->create(['title' => 'Ring the dentist.', 'position' => 1, 'estimated_seconds' => 120]);

    expect(nextAction($user, $now)?->step->title)->toBe('Ring the dentist.');
});

it('cools a skipped step off and gives it full standing back afterwards', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create();

    $skipped = Step::factory()->for($intention)->create(['title' => 'Ring the dentist.', 'position' => 1]);
    Step::factory()->for($intention)->create(['title' => 'Book the van.', 'position' => 2]);

    SkipStep::run($skipped);
    SkipStep::run($skipped->refresh());

    expect(nextAction($user)?->step->title)->toBe('Book the van.')
        ->and(nextAction($user, CarbonImmutable::now()->addHours(5))?->step->title)->toBe('Ring the dentist.');
});

it('holds back an intention the model could not name, even when it is the only work', function (): void {
    $user = User::factory()->create();

    $unclear = Intention::factory()->decomposed()->unclear()->for($user)->create(['title' => 'Sort the thing out']);
    Step::factory()->for($unclear)->create(['position' => 1]);

    expect(nextAction($user))->toBeNull();
});

it('never offers a step of an intention nobody has decomposed', function (): void {
    $user = User::factory()->create();

    $undecomposed = Intention::factory()->active()->for($user)->create();
    Step::factory()->for($undecomposed)->create(['position' => 1]);

    expect(nextAction($user))->toBeNull();
});

it('returns the same step over two runs of the same world', function (): void {
    $user = User::factory()->create();
    $now = CarbonImmutable::parse('2026-09-19 09:00:00');

    foreach (range(1, 4) as $index) {
        $intention = Intention::factory()->decomposed()->for($user)->create(['created_at' => $now->subDays($index)]);
        Step::factory()->count(3)->for($intention)->sequence(
            ['position' => 1],
            ['position' => 2],
            ['position' => 3],
        )->create(['estimated_seconds' => 600]);
    }

    $answers = collect(range(1, 3))->map(fn (): ?NextActionData => nextAction($user, $now));

    expect($answers->pluck('step.id')->unique())->toHaveCount(1);
});

it('explains every step it offers', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create();
    Step::factory()->for($intention)->create(['position' => 1, 'estimated_seconds' => null]);

    $answer = nextAction($user);

    expect($answer?->why)->toBe(['This is simply what comes next.']);
});

it('leaves work that belongs to somebody else alone', function (): void {
    $user = User::factory()->create();
    $stranger = Intention::factory()->decomposed()->create();
    Step::factory()->for($stranger)->create(['position' => 1]);

    expect(nextAction($user))->toBeNull();
});
