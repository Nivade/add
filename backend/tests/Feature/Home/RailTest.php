<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

it("counts the day in the person's zone rather than the server's", function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-21 07:30:00', 'UTC'));

    // 09:30 in Amsterdam, which is the clock the strip has to draw.
    $this->actingAs(User::factory()->create(['timezone' => 'Europe/Amsterdam']))
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('rail.nowMinute', 9 * 60 + 30)
            ->where('rail.marks', [])
            ->where('rail.appointmentTitle', null)
        );
});

it('marks every rung of the plan and the appointment itself', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-21 09:00:00', 'UTC'));

    $user = User::factory()->create(['timezone' => 'UTC']);

    CalendarEvent::factory()->for($user)->create([
        'title' => 'Dentist',
        'starts_at' => CarbonImmutable::parse('2026-09-21 14:00:00', 'UTC'),
        'travel_seconds' => 1800,
        'preparation_seconds' => 600,
        'gathering_seconds' => 300,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('rail.marks', [
                ['rung' => 'find_things', 'minute' => 13 * 60 + 15],
                ['rung' => 'get_ready', 'minute' => 13 * 60 + 20],
                ['rung' => 'leave', 'minute' => 13 * 60 + 30],
                ['rung' => null, 'minute' => 14 * 60],
            ])
            ->where('rail.appointmentTitle', 'Dentist')
        );
});

it('leaves a rung that falls before midnight off the strip', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-21 00:01:00', 'UTC'));

    $user = User::factory()->create(['timezone' => 'UTC']);

    CalendarEvent::factory()->for($user)->create([
        'title' => 'Night train',
        'starts_at' => CarbonImmutable::parse('2026-09-21 00:35:00', 'UTC'),
        'travel_seconds' => 1800,
        'preparation_seconds' => 600,
        'gathering_seconds' => 300,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('rail.marks', [
                ['rung' => 'leave', 'minute' => 5],
                ['rung' => null, 'minute' => 35],
            ])
        );
});

it('leaves the marks off when the appointment is another day', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-21 09:00:00', 'UTC'));

    $user = User::factory()->create(['timezone' => 'UTC']);

    Intention::factory()->decomposed()->for($user)->create([
        'title' => 'Renew the passport',
        'deadline_at' => CarbonImmutable::parse('2026-09-24 14:00:00', 'UTC'),
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('rail.marks', [])
            ->where('rail.appointmentTitle', null)
        );
});

it("draws the offered step's estimate on home", function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create();
    Step::factory()->for($intention)->create(['position' => 1, 'estimated_seconds' => 240]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('rail.stepSeconds', 240)
        );
});

it('draws no step block once a session is running', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->decomposed()->for($user)->create();
    $step = Step::factory()->for($intention)->create(['position' => 1, 'estimated_seconds' => 240]);
    ExecutionSession::factory()->for($user)->for($intention)->create(['current_step_id' => $step->id]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('rail.stepSeconds', null)
        );
});
