<?php

declare(strict_types=1);

use App\Enums\PlanRung;
use App\Models\Intention;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    CarbonImmutable::setTestNow('2026-09-19 09:00:00');
});

it('takes a stated travel time and answers with the replanned day', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->for($user)->create([
        'deadline_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
    ]);

    $this->actingAs($user)
        ->patchJson("/api/v1/intentions/{$intention->id}/plan", [PlanRung::Leave->value => 50])
        ->assertOk()
        ->assertJsonPath('rungs.2.clock', '13:10')
        ->assertJsonPath('rungs.2.assumed', false);

    expect($intention->refresh()->travel_seconds)->toBe(3000);
});

it('refuses a minute count that is not a number of minutes', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->for($user)->create([
        'deadline_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
    ]);

    $this->actingAs($user)
        ->patchJson("/api/v1/intentions/{$intention->id}/plan", [PlanRung::Leave->value => 'soon'])
        ->assertJsonValidationErrorFor(PlanRung::Leave->value);
});

it('does not tell one person that another person has an appointment', function (): void {
    $intention = Intention::factory()->create(['deadline_at' => CarbonImmutable::parse('2026-09-19 14:00:00')]);

    $this->actingAs(User::factory()->create())
        ->patchJson("/api/v1/intentions/{$intention->id}/plan", [PlanRung::Leave->value => 50])
        ->assertNotFound();
});

it('shows the plan on home and takes an edit from there', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->active()->for($user)->create([
        'title' => 'Dentist',
        'deadline_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('home.comingUp.plan.deadlineClock', '14:00')
            ->where('home.comingUp.plan.rungs.2.clock', '13:30')
            ->where('home.comingUp.plan.rungs.2.assumed', true)
        );

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('intentions.plan', $intention), [PlanRung::Leave->value => 45])
        ->assertRedirect(route('home'));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('home.comingUp.plan.rungs.2.clock', '13:15')
            ->where('home.comingUp.plan.rungs.2.assumed', false)
        );
});
