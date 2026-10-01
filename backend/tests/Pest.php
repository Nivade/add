<?php

declare(strict_types=1);

use App\Actions\Sessions\CompleteStep;
use App\Actions\Sessions\StartSession;
use App\Actions\Sessions\StopSession;
use App\Enums\ExecutionEventType;
use App\Enums\Place;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nvade\AiToolkit\Facades\AiToolkit;
use Nvade\AiToolkit\Testing\FakeAiProvider;
use Pest\Preset;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Arch Presets
|--------------------------------------------------------------------------
|
| Pest's own "laravel" preset reserves App\Attributes for classes implementing
| Illuminate\Contracts\Container\ContextualAttribute, and that rule's
| toImplement() check cannot be scoped with ->ignoring() — it fires eagerly,
| inside __call(), before the preset's return value ever receives our
| ->ignoring() call. This is the same preset with that one rule removed.
|
*/

Preset::custom('laravelMinusAttributes', fn (): array => [
    expect('App\Traits')->toBeTraits(),

    expect('App\Concerns')->toBeTraits(),

    expect('App')->not->toBeEnums()->ignoring('App\Enums'),

    expect('App\Enums')->toBeEnums()->ignoring('App\Enums\Concerns'),

    expect('App\Features')->toBeClasses()->ignoring('App\Features\Concerns'),

    expect('App\Features')->toHaveMethod('resolve')->ignoring('App\Features\Concerns'),

    expect('App\Exceptions')->classes()->toImplement('Throwable')->ignoring('App\Exceptions\Handler'),

    expect('App')->not->toImplement(Throwable::class)->ignoring('App\Exceptions'),

    expect('App\Http\Middleware')->classes()->toHaveMethod('handle'),

    expect('App\Models')->classes()->toExtend(Illuminate\Database\Eloquent\Model::class)->ignoring('App\Models\Scopes'),

    expect('App\Models')->classes()->not->toHaveSuffix('Model'),

    expect('App')->not->toExtend(Illuminate\Database\Eloquent\Model::class)->ignoring('App\Models'),

    expect('App\Http\Requests')->classes()->toHaveSuffix('Request'),

    expect('App\Http\Requests')->classes()->toExtend(Illuminate\Foundation\Http\FormRequest::class),

    expect('App\Http\Requests')->toHaveMethod('rules'),

    expect('App')->not->toExtend(Illuminate\Foundation\Http\FormRequest::class)->ignoring('App\Http\Requests'),

    expect('App\Console\Commands')->classes()->toHaveSuffix('Command'),

    expect('App\Console\Commands')->classes()->toExtend(Illuminate\Console\Command::class),

    expect('App\Console\Commands')->classes()->toHaveMethod('handle'),

    expect('App')->not->toExtend(Illuminate\Console\Command::class)->ignoring('App\Console\Commands'),

    expect('App\Mail')->classes()->toExtend(Illuminate\Mail\Mailable::class),

    expect('App\Mail')->classes()->toImplement(Illuminate\Contracts\Queue\ShouldQueue::class),

    expect('App')->not->toExtend(Illuminate\Mail\Mailable::class)->ignoring('App\Mail'),

    expect('App\Jobs')->classes()->toImplement(Illuminate\Contracts\Queue\ShouldQueue::class),

    expect('App\Jobs')->classes()->toHaveMethod('handle'),

    expect('App\Listeners')->toHaveMethod('handle'),

    expect('App\Notifications')->classes()->toExtend(Illuminate\Notifications\Notification::class),

    expect('App')->not->toExtend(Illuminate\Notifications\Notification::class)->ignoring('App\Notifications'),

    expect('App\Providers')->toHaveSuffix('ServiceProvider'),

    expect('App\Providers')->classes()->toExtend(Illuminate\Support\ServiceProvider::class),

    expect('App\Providers')->not->toBeUsed(),

    expect('App')->not->toExtend(Illuminate\Support\ServiceProvider::class)->ignoring('App\Providers'),

    expect('App')->not->toHaveSuffix('ServiceProvider')->ignoring('App\Providers'),

    expect('App')->not->toHaveSuffix('Controller')->ignoring('App\Http\Controllers'),

    expect('App\Http\Controllers')->classes()->toHaveSuffix('Controller'),

    expect('App\Http')->toOnlyBeUsedIn(['App\Http', 'App\Providers']),

    expect('App\Http\Controllers')->not->toHavePublicMethodsBesides(['__construct', '__invoke', 'index', 'show', 'create', 'store', 'edit', 'update', 'destroy', 'middleware']),

    expect(['dd', 'ddd', 'dump', 'env', 'exit', 'ray'])->not->toBeUsed(),

    expect('App\Policies')->classes()->toHaveSuffix('Policy'),
]);

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

// A test that reaches the AI path without seeding an answer fails rather than collecting a canned one.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn (): mixed => app()->instance(FakeAiProvider::class, AiToolkit::fake()))
    ->in('Feature', 'Browser');

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
function kitchen(int $steps = 3, ?User $user = null): Intention
{
    $intention = Intention::factory()
        ->decomposed()
        ->for($user ?? User::factory())
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

/** An intention with a deadline read out of what they wrote, two days off and not yet confirmed. */
function dated(User $user): Intention
{
    $intention = Intention::factory()->decomposed()->for($user)->create([
        'title' => 'Renew the passport',
        'deadline_at' => CarbonImmutable::now()->addDays(2),
    ]);

    Step::factory()->for($intention)->create(['position' => 1]);

    return $intention;
}

function started(int $steps = 3): ExecutionSession
{
    $intention = kitchen($steps);

    return StartSession::run($intention->user, $intention->steps()->first());
}

/** Evidence of where the person is, laid down the way the app lays it down. */
function finishStepAt(User $user, Place $place): void
{
    $intention = Intention::factory()->decomposed()->for($user)->create();
    $step = Step::factory()->for($intention)->create(['position' => 1, 'place' => $place]);

    CompleteStep::run(StartSession::run($user, $step), $step->id);
}

function workedOn(User $user): void
{
    StopSession::run(StartSession::run($user, kitchen(user: $user)->steps()->first()));
}

/** An account opened some days ago that stopped a session yesterday, with the clock left at 2026-09-30 10:00. */
function activeFor(int $days = 30): User
{
    test()->travelTo(CarbonImmutable::parse('2026-09-30 10:00:00')->subDays($days));
    $user = User::factory()->create();

    test()->travelTo(CarbonImmutable::parse('2026-09-29 10:00:00'));
    workedOn($user);

    test()->travelTo(CarbonImmutable::parse('2026-09-30 10:00:00'));

    return $user;
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

function fakeAi(): FakeAiProvider
{
    return app(FakeAiProvider::class);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function parsedCapture(array $overrides = []): array
{
    return [
        'title' => 'Clean the apartment',
        'why' => null,
        'deadline_at' => null,
        'clarifying_question' => null,
        'kind' => 'thought',
        'waiting_on' => null,
        ...$overrides,
    ];
}
