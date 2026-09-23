<?php

declare(strict_types=1);

use App\Actions\Overwhelm\ReduceToOneStep;
use App\Actions\Sessions\PauseSession;
use App\Actions\Sessions\StartSession;
use App\Actions\Sessions\StopSession;
use App\Data\ExecutionStateData;
use App\Models\CalendarEvent;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use Carbon\CarbonImmutable;

it('says how long the session has been running, in words rather than a countdown', function (): void {
    CarbonImmutable::setTestNow('2026-09-19 09:00:00');

    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create();
    $step = Step::factory()->for($intention)->create(['position' => 1]);

    $session = StartSession::run($user, $step);

    expect(ExecutionStateData::of($session)->elapsed)->toBe('You have just started.');

    CarbonImmutable::setTestNow('2026-09-19 09:12:00');

    expect(ExecutionStateData::of($session->refresh())->elapsed)->toBe('You have been working for 12 minutes.');
});

it('holds the elapsed count still while the session is paused', function (): void {
    CarbonImmutable::setTestNow('2026-09-19 09:00:00');

    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create();
    $step = Step::factory()->for($intention)->create(['position' => 1]);

    $session = StartSession::run($user, $step);

    CarbonImmutable::setTestNow('2026-09-19 09:12:00');
    PauseSession::run($session);

    CarbonImmutable::setTestNow('2026-09-19 10:30:00');

    expect(ExecutionStateData::of($session->refresh())->elapsed)->toBe('You had been working for 12 minutes.');
});

it('speaks of an ended session in the past tense', function (): void {
    CarbonImmutable::setTestNow('2026-09-19 09:00:00');

    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create();
    $step = Step::factory()->for($intention)->create(['position' => 1]);

    $session = StartSession::run($user, $step);

    CarbonImmutable::setTestNow('2026-09-19 09:12:00');
    StopSession::run($session);

    CarbonImmutable::setTestNow('2026-09-19 10:30:00');

    expect(ExecutionStateData::of($session->refresh())->elapsed)->toBe('You had been working for 12 minutes.');
});

it('counts the time left before leaving, and says so when the one step fits in it', function (): void {
    CarbonImmutable::setTestNow('2026-09-19 12:30:00');

    $user = User::factory()->create();
    CalendarEvent::factory()->for($user)->create(['starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00')]);

    $intention = Intention::factory()->decomposed()->for($user)->create();
    Step::factory()->for($intention)->create(['position' => 1, 'estimated_seconds' => 300]);

    $context = ResolutionContext::forUser($user);

    expect($context->availableSeconds)->toBe(3600)
        ->and($context->availableInWords())->toBe('60 minutes')
        ->and(ReduceToOneStep::run($user, $context)->smallestStep?->why)->toBe([
            'This takes about 5 minutes.',
            'It is the only thing left.',
            'You have 60 minutes before you need to leave, so it fits.',
        ]);
});

it('leaves the day open when nothing is booked in it', function (): void {
    CarbonImmutable::setTestNow('2026-09-19 12:30:00');

    $context = ResolutionContext::forUser(User::factory()->create());

    expect($context->availableSeconds)->toBeNull()
        ->and($context->availableInWords())->toBeNull();
});

it('reports no time left once the leave-by moment has gone past', function (): void {
    CarbonImmutable::setTestNow('2026-09-19 13:45:00');

    $user = User::factory()->create();
    CalendarEvent::factory()->for($user)->create(['starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00')]);

    expect(ResolutionContext::forUser($user)->availableSeconds)->toBe(0);
});
