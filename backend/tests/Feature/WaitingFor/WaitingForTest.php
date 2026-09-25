<?php

declare(strict_types=1);

use App\Enums\WaitingForStatus;
use App\Models\User;
use App\Models\WaitingFor;
use Inertia\Testing\AssertableInertia;

it('creates a waiting-for with nothing but who and what', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('waiting-fors.store'), ['subject' => 'John', 'note' => 'the contract'])
        ->assertRedirect(route('home'));

    $waitingFor = WaitingFor::query()->sole();

    expect($waitingFor->subject)->toBe('John')
        ->and($waitingFor->note)->toBe('the contract')
        ->and($waitingFor->status)->toBe(WaitingForStatus::Waiting);
});

it('does not surface a fresh waiting-for on home', function (): void {
    $user = User::factory()->create();
    WaitingFor::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('home.needsAttention', 0));
});

it('surfaces a stale waiting-for on home with all four responses reachable', function (): void {
    $user = User::factory()->create();
    $waitingFor = WaitingFor::factory()->for($user)->stale()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
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
        ->assertInertia(fn (AssertableInertia $page) => $page
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
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('home.needsAttention', 0));
});

it('answers over the API too', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.waiting-fors.store'), ['subject' => 'Sarah', 'note' => 'the invoice'])
        ->assertCreated()
        ->assertJsonPath('subject', 'Sarah');

    $waitingFor = WaitingFor::query()->sole();

    $this->actingAs($user)
        ->postJson(route('api.v1.waiting-fors.respond', $waitingFor), ['response' => 'wait_longer'])
        ->assertOk()
        ->assertJsonPath('status', 'waiting');
});

it('does not let one person respond for another', function (): void {
    $waitingFor = WaitingFor::factory()->create();

    $this->actingAs(User::factory()->create())
        ->postJson(route('api.v1.waiting-fors.respond', $waitingFor), ['response' => 'cancel'])
        ->assertNotFound();

    expect($waitingFor->refresh()->status)->toBe(WaitingForStatus::Waiting);
});
