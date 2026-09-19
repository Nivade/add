<?php

declare(strict_types=1);

use App\Actions\Sessions\StartSession;
use App\Enums\ExecutionEventType;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function repoPath(string $relative): string
{
    return base_path('../'.ltrim($relative, '/'));
}

/** A decomposed intention whose steps get longer as they go, so "shortest" and "next" differ. */
function kitchen(int $steps = 3): Intention
{
    $intention = Intention::factory()
        ->decomposed()
        ->for(User::factory())
        ->create(['title' => 'Clean the kitchen']);

    foreach (range(1, $steps) as $position) {
        Step::factory()->for($intention)->create([
            'title' => "Step {$position}.",
            'position' => $position,
            'estimated_seconds' => $position * 60,
        ]);
    }

    return $intention;
}

function started(int $steps = 3): ExecutionSession
{
    $intention = kitchen($steps);

    return StartSession::run($intention->user, $intention->steps()->first());
}

/** @return list<string> */
function replay(ExecutionSession $session): array
{
    return $session->events()
        ->oldest()
        ->orderBy('id')
        ->pluck('type')
        ->map(fn (ExecutionEventType $type): string => $type->value)
        ->all();
}
