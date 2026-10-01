<?php

declare(strict_types=1);

use App\Actions\Captures\RecordCapture;
use App\Enums\Ai\AiOperation;
use App\Enums\CaptureKind;
use App\Enums\CommitmentProvenance;
use App\Enums\CommitmentStatus;
use App\Enums\WaitingForStatus;
use App\Models\Capture;
use App\Models\Commitment;
use App\Models\ExecutionSession;
use App\Models\FutureReminder;
use App\Models\Intention;
use App\Models\User;
use App\Models\WaitingFor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Nvade\AiToolkit\AiRequest;

function sortedAs(User $user, CaptureKind $kind, string $body = 'remind me tomorrow at 9 to call Dr Jansen'): Capture
{
    fakeAi()
        ->respondFor(AiOperation::ParseCapture->value, parsedCapture([
            'kind' => $kind->value,
            'title' => 'Call Dr Jansen',
            'waiting_on' => $kind === CaptureKind::WaitingFor ? 'Dr Jansen' : null,
        ]))
        ->respondFor(AiOperation::DecomposeIntention->value, ['steps' => [['title' => 'Open the phone.', 'estimated_seconds' => 30]]]);

    return RecordCapture::run($user, $body)->refresh();
}

/** @return array<string, int> */
function routedRows(): array
{
    return [
        'thought' => Intention::query()->count(),
        'waiting_for' => WaitingFor::query()->count(),
        'promise' => Commitment::query()->count(),
        'reminder' => FutureReminder::query()->count(),
    ];
}

it('changes a sorted capture into any other kind, and asks the model nothing more', function (CaptureKind $from, CaptureKind $to): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-16 10:00:00', 'UTC'));
    $user = User::factory()->create();
    $capture = sortedAs($user, $from);

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('captures.kind', $capture), ['kind' => $to->value])
        ->assertRedirect(route('home'));

    $capture->refresh();

    expect($capture->kind)->toBe($to)
        ->and($capture->kind_confirmed_at)->not->toBeNull()
        ->and(routedRows())->toBe([...array_fill_keys(['thought', 'waiting_for', 'promise', 'reminder'], 0), $to->value => 1])
        ->and($capture->intention_id)->toBe($to === CaptureKind::Thought ? $capture->routed_id : null);

    // One parse when it was captured; a decomposition only for each side that is a thought.
    fakeAi()->assertSentCount(1 + (int) ($from === CaptureKind::Thought) + (int) ($to === CaptureKind::Thought));
})->with(function (): Generator {
    foreach (CaptureKind::answerable() as $from) {
        foreach (CaptureKind::answerable() as $to) {
            if ($from !== $to) {
                yield "{$from->value} to {$to->value}" => [$from, $to];
            }
        }
    }
});

it('keeps a capture it thought was not for them, as a thought, when they say so', function (): void {
    $user = User::factory()->create();
    $body = "Dear customer,\n\nThanks for your recent purchase. Your receipt is attached.";
    $provider = fakeAi()
        ->respondFor(AiOperation::ClassifyIngestion->value, ['actionable' => false, 'title' => null, 'why' => null, 'deadline_at' => null, 'estimated_seconds' => null])
        ->respondFor(AiOperation::DecomposeIntention->value, ['steps' => [['title' => 'Open the receipt.', 'estimated_seconds' => 30]]]);
    $capture = RecordCapture::run($user, $body)->refresh();

    expect($capture->kind)->toBe(CaptureKind::NotForYou);

    $this->actingAs($user)
        ->post(route('captures.kind', $capture), ['kind' => CaptureKind::Thought->value])
        ->assertRedirect();

    expect($capture->refresh()->kind)->toBe(CaptureKind::Thought)
        ->and(Intention::query()->sole()->title)->toBe(Str::limit($body, 80));

    $provider->assertNotSent(fn (AiRequest $request): bool => $request->operation === AiOperation::ParseCapture->value);
});

it('refuses to change a capture whose row was already acted on', function (CaptureKind $kind, Closure $act): void {
    $user = User::factory()->create();
    $capture = sortedAs($user, $kind);
    $act($capture);

    $this->actingAs($user)
        ->postJson(route('api.v1.captures.kind', $capture), ['kind' => CaptureKind::Thought->value])
        ->assertConflict();

    expect($capture->refresh()->kind)->toBe($kind);
})->with([
    'an intention with a session' => [CaptureKind::Thought, fn (Capture $capture): mixed => ExecutionSession::factory()->create(['user_id' => $capture->user_id, 'intention_id' => $capture->routed_id])],
    'a waiting-for that arrived' => [CaptureKind::WaitingFor, fn (Capture $capture): mixed => WaitingFor::query()->whereKey($capture->routed_id)->update(['status' => WaitingForStatus::Received])],
    'a promise kept' => [CaptureKind::Promise, fn (Capture $capture): mixed => Commitment::query()->whereKey($capture->routed_id)->update(['status' => CommitmentStatus::Kept])],
    'a reminder already sent' => [CaptureKind::Reminder, fn (Capture $capture): mixed => FutureReminder::query()->whereKey($capture->routed_id)->update(['sent_at' => now()])],
]);

it("does not let anyone change or confirm someone else's capture", function (string $route): void {
    $capture = sortedAs(User::factory()->create(), CaptureKind::Promise);

    $this->actingAs(User::factory()->create())
        ->postJson(route($route, $capture), ['kind' => CaptureKind::Thought->value])
        ->assertNotFound();

    expect($capture->refresh()->kind)->toBe(CaptureKind::Promise)
        ->and($capture->kind_confirmed_at)->toBeNull();
})->with(['api.v1.captures.kind', 'api.v1.captures.confirm']);

it('offers not-for-you only as something the app decides', function (): void {
    $user = User::factory()->create();
    $capture = sortedAs($user, CaptureKind::Thought);

    $this->actingAs($user)
        ->postJson(route('api.v1.captures.kind', $capture), ['kind' => CaptureKind::NotForYou->value])
        ->assertJsonValidationErrors('kind');
});

it('confirms the sort, and with it a promise the app inferred', function (): void {
    $user = User::factory()->create();
    $capture = sortedAs($user, CaptureKind::Promise);

    expect(Commitment::query()->sole()->provenance)->toBe(CommitmentProvenance::SystemInferred);

    $this->actingAs($user)
        ->postJson(route('api.v1.captures.confirm', $capture))
        ->assertOk()
        ->assertJsonPath('kind', 'promise')
        ->assertJsonPath('kindConfirmedAt', fn (?string $at): bool => $at !== null);

    expect(Commitment::query()->sole()->confirmed_at)->not->toBeNull();
});
