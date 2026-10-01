<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

it('moves an inferred deadline to the time they give, as their own word', function (): void {
    $user = User::factory()->create();
    $intention = dated($user);

    $this->actingAs($user)
        ->post(route('intentions.deadline.correct', $intention), ['deadline_at' => '2026-10-05T14:30'])
        ->assertRedirect(route('home'));

    $intention->refresh();

    expect($intention->deadline_at?->equalTo(CarbonImmutable::parse('2026-10-05 14:30', 'UTC')))->toBeTrue()
        ->and($intention->deadline_inferred)->toBeFalse();
});

it('drops the deadline when there is none', function (): void {
    $user = User::factory()->create();
    $intention = dated($user);

    $this->actingAs($user)
        ->post(route('intentions.deadline.correct', $intention), ['deadline_at' => ''])
        ->assertRedirect(route('home'));

    expect($intention->refresh()->deadline_at)->toBeNull();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('home.comingUp', null));
});

it('reads the time on the API in their zone, and says it back in it', function (): void {
    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);
    $intention = dated($user);

    $this->actingAs($user)
        ->patchJson(route('api.v1.intentions.deadline.correct', $intention), ['deadline_at' => '2026-10-05T14:30'])
        ->assertOk()
        ->assertJsonPath('deadlineInferred', false);

    expect($intention->refresh()->deadline_at?->equalTo(CarbonImmutable::parse('2026-10-05 12:30', 'UTC')))->toBeTrue();

    $this->actingAs($user)
        ->getJson(route('api.v1.appointments.show', ['kind' => 'intention', 'id' => $intention->id]))
        ->assertJsonPath('localAt', '2026-10-05T14:30');
});

it('refuses a time it cannot read', function (): void {
    $user = User::factory()->create();
    $intention = dated($user);

    $this->actingAs($user)
        ->patchJson(route('api.v1.intentions.deadline.correct', $intention), ['deadline_at' => 'next friday'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('deadline_at');
});

it("does not let one person correct another person's deadline", function (): void {
    $intention = dated(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->post(route('intentions.deadline.correct', $intention), ['deadline_at' => '2026-10-05T14:30'])
        ->assertNotFound();

    expect($intention->refresh()->deadline_inferred)->toBeTrue();
});

it('opens the appointment page for their own appointment only', function (): void {
    $user = User::factory()->create();
    $intention = dated($user);

    $this->actingAs($user)
        ->get(route('appointments.show', ['kind' => 'intention', 'id' => $intention->id]))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('appointment')
            ->where('appointment.title', 'Renew the passport')
            ->where('appointment.inferred', true));

    $this->actingAs(User::factory()->create())
        ->get(route('appointments.show', ['kind' => 'intention', 'id' => $intention->id]))
        ->assertNotFound();
});
