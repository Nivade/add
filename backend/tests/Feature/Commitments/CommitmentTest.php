<?php

declare(strict_types=1);

use App\Actions\Sessions\CompleteStep;
use App\Enums\CommitmentProvenance;
use App\Enums\CommitmentStatus;
use App\Models\Commitment;
use App\Models\Intention;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

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

it("does not let one person promote another person's intention", function (): void {
    $intention = Intention::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('intentions.commitment', $intention))
        ->assertNotFound();

    expect(Commitment::query()->count())->toBe(0);
});

it('renders a system-inferred commitment as inferred and unconfirmed, never as fact', function (): void {
    $user = User::factory()->create();
    Commitment::factory()->for($user)->create(['created_at' => now()->subDay()]);
    $inferred = Commitment::factory()->for($user)->inferred()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('home.needsAttention', 1)
            ->where('home.needsAttention.0.kind', 'commitment')
            ->where('home.needsAttention.0.id', $inferred->id)
            ->where('home.needsAttention.0.awaitingConfirmation', true));

    $this->actingAs($user)
        ->get(route('commitments.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('commitments')
            ->has('list.commitments', 2)
            ->where('list.commitments.0.provenance', 'system_inferred')
            ->where('list.commitments.0.confirmedAt', null));
});

it('confirms an inferred commitment, then keeps it', function (): void {
    $user = User::factory()->create();
    $commitment = Commitment::factory()->for($user)->inferred()->create();

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('commitments.respond', $commitment), ['response' => 'confirm'])
        ->assertRedirect(route('home'));

    expect($commitment->refresh()->confirmed_at)->not->toBeNull()
        ->and($commitment->status)->toBe(CommitmentStatus::Open);

    $this->actingAs($user)->post(route('commitments.respond', $commitment), ['response' => 'keep']);

    expect($commitment->refresh()->status)->toBe(CommitmentStatus::Kept);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('home.needsAttention', 0));
});

it('lets go of a commitment for good', function (): void {
    $user = User::factory()->create();
    $commitment = Commitment::factory()->for($user)->create();

    $this->actingAs($user)->post(route('commitments.respond', $commitment), ['response' => 'release']);

    expect($commitment->refresh()->status)->toBe(CommitmentStatus::Released);

    $this->actingAs($user)
        ->postJson(route('api.v1.commitments.respond', $commitment), ['response' => 'keep'])
        ->assertConflict();
});

it('refuses to confirm what nobody inferred', function (): void {
    $user = User::factory()->create();
    $commitment = Commitment::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.commitments.respond', $commitment), ['response' => 'confirm'])
        ->assertConflict();
});

it("does not let one person answer another person's commitment", function (): void {
    $commitment = Commitment::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('commitments.respond', $commitment), ['response' => 'release'])
        ->assertNotFound();

    expect($commitment->refresh()->status)->toBe(CommitmentStatus::Open);
});

it('promotes an intention once, and keeps the commitment when the intention is finished', function (): void {
    $session = started(1);
    $intention = $session->intention;

    $this->actingAs($intention->user)->post(route('intentions.commitment', $intention));
    $this->actingAs($intention->user)->post(route('intentions.commitment', $intention));

    $this->actingAs($intention->user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('home.rightNowIsCommitment', true)
            ->has('home.needsAttention', 0));

    CompleteStep::run($session);

    expect(Commitment::query()->sole()->status)->toBe(CommitmentStatus::Kept);
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

it('lists open commitments over the API', function (): void {
    $user = User::factory()->create();
    Commitment::factory()->for($user)->create(['description' => "I'll bring the documents"]);
    Commitment::factory()->for($user)->create(['status' => CommitmentStatus::Kept]);
    Commitment::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.commitments.index'))
        ->assertOk()
        ->assertJsonCount(1, 'commitments')
        ->assertJsonPath('commitments.0.description', "I'll bring the documents")
        ->assertJsonPath('commitments.0.status', 'open');
});

it('refuses to promote an intention that is already done', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->for($user)->done()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.intentions.commitment', $intention))
        ->assertConflict();

    expect(Commitment::query()->count())->toBe(0);
});

it('reopens the one commitment when an intention is promoted again after letting it go', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->for($user)->create();

    $first = $this->actingAs($user)->postJson(route('api.v1.intentions.commitment', $intention))->assertCreated();
    $this->actingAs($user)->post(route('commitments.respond', $first->json('id')), ['response' => 'release']);

    $this->actingAs($user)
        ->postJson(route('api.v1.intentions.commitment', $intention))
        ->assertOk()
        ->assertJsonPath('status', 'open');

    expect(Commitment::query()->count())->toBe(1);
});

it('keeps the list reachable when every open commitment is tied to an intention', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->for($user)->create();
    $this->actingAs($user)->post(route('intentions.commitment', $intention));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('home.needsAttention', 0)
            ->where('home.hasOpenCommitments', true));
});

it('says where a commitment on home came from', function (): void {
    $user = User::factory()->create();
    Commitment::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('home.needsAttention.0.provenance', 'user_stated')
            ->where('home.needsAttention.0.awaitingConfirmation', false));
});

it('promotes the step on screen, and keeps the commitment when that step is done', function (): void {
    $session = started(2);
    $step = $session->currentStep()->sole();

    $this->actingAs($session->user)
        ->from(route('focus'))
        ->post(route('focus.commitment', $session))
        ->assertRedirect(route('focus'));

    $commitment = Commitment::query()->sole();

    expect($commitment->step_id)->toBe($step->id)
        ->and($commitment->description)->toBe($step->title)
        ->and($commitment->provenance)->toBe(CommitmentProvenance::UserTask);

    $this->actingAs($session->user)
        ->get(route('focus'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('state.currentStepIsCommitment', true));

    CompleteStep::run($session);

    expect($commitment->refresh()->status)->toBe(CommitmentStatus::Kept);
});

it('promotes the step on screen over the API too, and only for its owner', function (): void {
    $session = started(2);

    $this->actingAs(User::factory()->create())
        ->postJson(route('api.v1.sessions.commitment', $session))
        ->assertNotFound();

    $this->actingAs($session->user)
        ->postJson(route('api.v1.sessions.commitment', $session))
        ->assertCreated()
        ->assertJsonPath('provenance', 'user_task');
});
