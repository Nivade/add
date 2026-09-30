<?php

declare(strict_types=1);

use App\Enums\WaitingForStatus;
use App\Models\User;
use App\Models\WaitingFor;
use Inertia\Testing\AssertableInertia;

it('does not surface a fresh waiting-for on home', function (): void {
    $user = User::factory()->create();
    WaitingFor::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('home.needsAttention', 0));
});

it('surfaces a stale waiting-for on home with all four responses reachable', function (): void {
    $user = User::factory()->create();
    $waitingFor = WaitingFor::factory()->for($user)->stale()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('home.needsAttention.0.kind', 'waiting_for')
            ->where('home.needsAttention.0.id', $waitingFor->id)
            ->where('home.needsAttention.0.title', 'John'));

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('waiting-fors.respond', $waitingFor), ['response' => 'follow_up'])
        ->assertRedirect(route('home'));

    expect($waitingFor->refresh()->status)->toBe(WaitingForStatus::FollowedUp);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('home.needsAttention', 0));
});

it('retires a waiting-for marked received or cancelled', function (): void {
    $user = User::factory()->create();
    $received = WaitingFor::factory()->for($user)->stale()->create();
    $cancelled = WaitingFor::factory()->for($user)->stale()->create();

    $this->actingAs($user)
        ->post(route('waiting-fors.respond', $received), ['response' => 'receive']);
    $this->actingAs($user)
        ->post(route('waiting-fors.respond', $cancelled), ['response' => 'cancel']);

    expect($received->refresh()->status)->toBe(WaitingForStatus::Received)
        ->and($cancelled->refresh()->status)->toBe(WaitingForStatus::Cancelled);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('home.needsAttention', 0));
});

it('refuses to reopen a waiting-for once it is retired', function (): void {
    $user = User::factory()->create();
    $received = WaitingFor::factory()->for($user)->create(['status' => WaitingForStatus::Received]);

    $this->actingAs($user)
        ->postJson(route('api.v1.waiting-fors.respond', $received), ['response' => 'wait_longer'])
        ->assertConflict();

    expect($received->refresh()->status)->toBe(WaitingForStatus::Received);
});

it('counts staleness from the last answer, not from an unrelated edit', function (): void {
    $user = User::factory()->create();
    $waitingFor = WaitingFor::factory()->for($user)->stale()->create();
    $waitingFor->update(['note' => 'the signed contract']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('home.needsAttention', 1));

    $this->actingAs($user)->post(route('waiting-fors.respond', $waitingFor), ['response' => 'wait_longer']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('home.needsAttention', 0));
});

it('does not let one person respond for another', function (): void {
    $waitingFor = WaitingFor::factory()->create();

    $this->actingAs(User::factory()->create())
        ->postJson(route('api.v1.waiting-fors.respond', $waitingFor), ['response' => 'cancel'])
        ->assertNotFound();

    expect($waitingFor->refresh()->status)->toBe(WaitingForStatus::Waiting);
});
