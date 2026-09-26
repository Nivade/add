<?php

declare(strict_types=1);

use App\Enums\CommitmentProvenance;
use App\Models\Commitment;
use App\Models\Intention;
use App\Models\User;

it('creates a commitment typed directly, confirmed immediately', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('commitments.store'), ['description' => "I'll call Sarah Friday"])
        ->assertRedirect(route('home'));

    $commitment = Commitment::query()->sole();

    expect($commitment->description)->toBe("I'll call Sarah Friday")
        ->and($commitment->provenance)->toBe(CommitmentProvenance::UserStated)
        ->and($commitment->confirmed_at)->not->toBeNull();
});

it('promotes an existing intention to a commitment, confirmed immediately', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->for($user)->create(['title' => 'Send the contract back']);

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('intentions.commitment', $intention))
        ->assertRedirect(route('home'));

    $commitment = Commitment::query()->sole();

    expect($commitment->description)->toBe('Send the contract back')
        ->and($commitment->provenance)->toBe(CommitmentProvenance::UserTask)
        ->and($commitment->confirmed_at)->not->toBeNull();
});

it('does not let one person promote another person\'s intention', function (): void {
    $intention = Intention::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('intentions.commitment', $intention))
        ->assertNotFound();

    expect(Commitment::query()->count())->toBe(0);
});

it('renders a system-inferred commitment as inferred and unconfirmed, never as fact', function (): void {
    $user = User::factory()->create();
    $inferred = Commitment::factory()->for($user)->inferred()->create();

    expect($inferred->provenance)->toBe(CommitmentProvenance::SystemInferred)
        ->and($inferred->provenance->isInferred())->toBeTrue()
        ->and($inferred->confirmed_at)->toBeNull();
});

it('answers over the API too', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.commitments.store'), ['description' => "I'll bring the documents"])
        ->assertCreated()
        ->assertJsonPath('description', "I'll bring the documents")
        ->assertJsonPath('provenance', 'user_stated');

    $intention = Intention::factory()->for($user)->create(['title' => 'Book the movers']);

    $this->actingAs($user)
        ->postJson(route('api.v1.intentions.commitment', $intention))
        ->assertCreated()
        ->assertJsonPath('description', 'Book the movers')
        ->assertJsonPath('provenance', 'user_task');
});
