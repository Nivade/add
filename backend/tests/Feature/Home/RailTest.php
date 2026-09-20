<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Intention;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

it('counts the day in the person\'s zone rather than the server\'s', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-21 07:30:00', 'UTC'));

    // 09:30 in Amsterdam, which is the clock the rail has to draw.
    $this->actingAs(User::factory()->create(['timezone' => 'Europe/Amsterdam']))
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('rail.nowMinute', 9 * 60 + 30)
            ->where('rail.nowClock', '09:30')
            ->where('rail.leaveByMinute', null)
            ->where('rail.appointmentTitle', null)
        );
});

it('marks when to leave, not when the thing starts', function (): void {
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
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('rail.leaveByMinute', 13 * 60 + 30)
            ->where('rail.leaveByClock', '13:30')
            ->where('rail.appointmentTitle', 'Dentist')
        );
});

it('leaves the mark off when the appointment is another day', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-21 09:00:00', 'UTC'));

    $user = User::factory()->create(['timezone' => 'UTC']);

    Intention::factory()->decomposed()->for($user)->create([
        'title' => 'Renew the passport',
        'deadline_at' => CarbonImmutable::parse('2026-09-24 14:00:00', 'UTC'),
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('rail.leaveByMinute', null)
            ->where('rail.leaveByClock', null)
            ->where('rail.appointmentTitle', null)
        );
});
