<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Intention;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    CarbonImmutable::setTestNow('2026-09-19 09:00:00');
});

it('resolves an intention by id, the way a deep link does rather than trusting what home shows next', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->active()->for($user)->create([
        'title' => 'Dentist',
        'deadline_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
    ]);

    $this->actingAs($user)
        ->getJson("/api/v1/appointments/intention/{$intention->id}")
        ->assertOk()
        ->assertJsonPath('kind', 'intention')
        ->assertJsonPath('id', $intention->id)
        ->assertJsonPath('title', 'Dentist');
});

it('resolves a calendar event by id', function (): void {
    $user = User::factory()->create();
    $event = CalendarEvent::factory()->for($user)->create([
        'title' => 'Flight',
        'starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00'),
    ]);

    $this->actingAs($user)
        ->getJson("/api/v1/appointments/calendar_event/{$event->id}")
        ->assertOk()
        ->assertJsonPath('kind', 'calendar_event')
        ->assertJsonPath('id', (string) $event->id)
        ->assertJsonPath('title', 'Flight');
});

it('answers a null-shaped 200 for an appointment with no time to plan backwards from', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->for($user)->create(['deadline_at' => null]);

    $this->actingAs($user)
        ->getJson("/api/v1/appointments/intention/{$intention->id}")
        ->assertOk()
        ->assertContent('null');
});

it('does not tell one person that another person has an appointment', function (): void {
    $intention = Intention::factory()->create(['deadline_at' => CarbonImmutable::parse('2026-09-19 14:00:00')]);

    $this->actingAs(User::factory()->create())
        ->getJson("/api/v1/appointments/intention/{$intention->id}")
        ->assertNotFound();
});

it('answers not found for an id that does not exist', function (): void {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/v1/appointments/intention/01hzzzzzzzzzzzzzzzzzzzzzzz')
        ->assertNotFound();
});
