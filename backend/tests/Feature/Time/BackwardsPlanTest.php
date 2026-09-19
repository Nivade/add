<?php

declare(strict_types=1);

use App\Actions\Time\AdjustPlanAssumptions;
use App\Data\BackwardsPlanData;
use App\Enums\PlanRung;
use App\Models\Intention;
use App\Models\User;
use App\Support\Time\BackwardsPlan;
use Carbon\CarbonImmutable;

function planFor(Intention $intention, string $now, string $timezone = 'UTC'): ?BackwardsPlanData
{
    return BackwardsPlan::for($intention, CarbonImmutable::parse($now, $timezone));
}

it('counts a 14:00 appointment backwards through find, get ready and leave', function (): void {
    $intention = Intention::factory()->create(['deadline_at' => CarbonImmutable::parse('2026-09-19 14:00:00')]);

    $plan = planFor($intention, '2026-09-19 09:00:00');

    expect($plan?->deadlineClock)->toBe('14:00')
        ->and(array_map(fn ($rung): array => [$rung->rung, $rung->clock], $plan?->rungs ?? []))->toBe([
            [PlanRung::FindThings, '13:00'],
            [PlanRung::GetReady, '13:10'],
            [PlanRung::Leave, '13:30'],
        ]);
});

it('marks every rung it invented as assumed, and stops once the person says otherwise', function (): void {
    $intention = Intention::factory()->create(['deadline_at' => CarbonImmutable::parse('2026-09-19 14:00:00')]);

    expect(array_map(fn ($rung): bool => $rung->assumed, planFor($intention, '2026-09-19 09:00:00')?->rungs ?? []))
        ->toBe([true, true, true]);

    AdjustPlanAssumptions::run($intention, [PlanRung::Leave->value => 50]);

    $plan = planFor($intention->refresh(), '2026-09-19 09:00:00');

    expect(array_map(fn ($rung): array => [$rung->clock, $rung->assumed], $plan?->rungs ?? []))->toBe([
        ['12:40', true],
        ['12:50', true],
        ['13:10', false],
    ]);
});

it('returns a rung to its assumption when the person clears what they stated', function (): void {
    $intention = Intention::factory()->create([
        'deadline_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
        'travel_seconds' => 3000,
    ]);

    AdjustPlanAssumptions::run($intention, [PlanRung::Leave->value => null]);

    $leave = planFor($intention->refresh(), '2026-09-19 09:00:00')?->rungs[2];

    expect($leave?->clock)->toBe('13:30')
        ->and($leave?->assumed)->toBeTrue();
});

it('says a rung has gone past rather than scoring it', function (): void {
    $intention = Intention::factory()->create(['deadline_at' => CarbonImmutable::parse('2026-09-19 14:00:00')]);

    $plan = planFor($intention, '2026-09-19 13:05:00');

    expect(array_map(fn ($rung): bool => $rung->alreadyPassed, $plan?->rungs ?? []))->toBe([true, false, false]);
});

it('counts backwards in the person\'s zone, not the column\'s', function (): void {
    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);
    $intention = Intention::factory()->for($user)->create([
        'deadline_at' => CarbonImmutable::parse('2026-09-19 12:00:00', 'UTC'),
    ]);

    $plan = planFor($intention, '2026-09-19 09:00:00', 'Europe/Amsterdam');

    expect($plan?->deadlineClock)->toBe('14:00')
        ->and($plan?->rungs[2]->clock)->toBe('13:30');
});

it('plans nothing for a day that is not today, and nothing without a deadline', function (): void {
    $tomorrow = Intention::factory()->create(['deadline_at' => CarbonImmutable::parse('2026-09-20 14:00:00')]);
    $undated = Intention::factory()->create(['deadline_at' => null]);

    expect(planFor($tomorrow, '2026-09-19 09:00:00'))->toBeNull()
        ->and(planFor($undated, '2026-09-19 09:00:00'))->toBeNull();
});
