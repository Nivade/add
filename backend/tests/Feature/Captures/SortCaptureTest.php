<?php

declare(strict_types=1);

use App\Actions\Ai\UpdateAiConsent;
use App\Actions\Captures\ChangeCaptureKind;
use App\Actions\Captures\RecordCapture;
use App\Actions\Captures\SortCapture;
use App\Actions\Home\BuildHome;
use App\Enums\Ai\AiOperation;
use App\Enums\CaptureKind;
use App\Enums\CommitmentProvenance;
use App\Enums\IntentionStatus;
use App\Enums\Place;
use App\Exceptions\AiConsentRequired;
use App\Models\Capture;
use App\Models\Commitment;
use App\Models\FutureReminder;
use App\Models\Intention;
use App\Models\User;
use App\Models\WaitingFor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Nvade\AiToolkit\AiRequest;
use Nvade\AiToolkit\Exceptions\AiResponseInvalid;
use Nvade\AiToolkit\Testing\FakeAiProvider;

/**
 * @param  array<string, mixed>  $parse
 * @param  array<string, mixed>|null  $decompose
 */
function answeredAi(array $parse = [], ?array $decompose = null): FakeAiProvider
{
    return fakeAi()
        ->respondFor(AiOperation::ParseCapture->value, parsedCapture($parse))
        ->respondFor(AiOperation::DecomposeIntention->value, $decompose ?? ['steps' => [['title' => 'Grab a bin bag.', 'estimated_seconds' => 60]]]);
}

it('turns one typed sentence into an intention with usable steps, with no API key set', function (): void {
    config()->set('ai-toolkit.driver', 'canned');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/captures', ['body' => 'I need to clean the apartment before Saturday because my parents are coming'])
        ->assertCreated()
        ->assertJsonPath('source', 'text');

    $capture = Capture::query()->sole();
    $intention = Intention::query()->sole();

    expect($capture->processed_at)->not->toBeNull()
        ->and($capture->intention_id)->toBe($intention->id)
        ->and($intention->status)->toBe(IntentionStatus::Active)
        ->and($intention->decomposed_at)->not->toBeNull()
        ->and($intention->deadline_at?->isSaturday())->toBeTrue()
        ->and($intention->steps)->not->toBeEmpty()
        ->and($intention->steps->first()->position)->toBe(1)
        ->and($intention->steps->first()->generated)->toBeTrue();
});

it('returns the capture before anything is parsed', function (): void {
    Queue::fake();
    config()->set('ai-toolkit.driver', 'canned');

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/captures', ['body' => 'buy dishwasher tablets'])
        ->assertCreated()
        ->assertJsonPath('body', 'buy dishwasher tablets')
        ->assertJsonPath('intentionId', null);

    expect(Capture::query()->sole()->processed_at)->toBeNull()
        ->and(Intention::query()->count())->toBe(0);
});

it('refuses an unauthenticated capture', function (): void {
    $this->postJson('/api/v1/captures', ['body' => 'buy dishwasher tablets'])->assertUnauthorized();
});

it('requires something to capture and nothing else', function (): void {
    config()->set('ai-toolkit.driver', 'canned');

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/captures', ['body' => ''])
        ->assertJsonValidationErrors('body');
});

it('lets the deadline extractor beat the model when the two disagree', function (): void {
    $provider = answeredAi(['deadline_at' => '2030-01-01T09:00:00+00:00']);
    $this->travelTo(CarbonImmutable::parse('2026-09-16 10:00:00'));

    RecordCapture::run(User::factory()->create(), 'clean the apartment before Saturday');

    expect(Intention::query()->sole()->deadline_at?->toDateTimeString())->toBe('2026-09-19 23:59:59');

    $provider->assertSent(fn (AiRequest $request): bool => $request->operation === AiOperation::ParseCapture->value
        && str_contains($request->user, 'clean the apartment')
        && ! str_contains($request->user, 'before Saturday'));
});

it('tells the model what day it is and which zone to answer in', function (): void {
    $provider = answeredAi();
    $this->travelTo(CarbonImmutable::parse('2026-09-16 23:30:00', 'UTC'));

    RecordCapture::run(
        User::factory()->create(['timezone' => 'Europe/Amsterdam']),
        'renew my passport'
    );

    // Half past midnight in Amsterdam, so the day the model is told is not the server's.
    $provider->assertSent(fn (AiRequest $request): bool => $request->operation === AiOperation::ParseCapture->value
        && str_contains($request->user, 'Thursday 17 September 2026')
        && str_contains($request->user, 'Europe/Amsterdam'));
});

it("reads a deadline only the model found on the person's clock", function (): void {
    answeredAi(['deadline_at' => '2026-10-02T09:00:00']);

    RecordCapture::run(
        User::factory()->create(['timezone' => 'Europe/Amsterdam']),
        'renew my passport'
    );

    // 09:00 in Amsterdam, which is 07:00 UTC, which is what the column holds.
    expect(Intention::query()->sole()->deadline_at?->toDateTimeString())->toBe('2026-10-02 07:00:00');
});

it('reads "tomorrow morning" in the person\'s zone and stores the instant it names', function (): void {
    answeredAi();
    $this->travelTo(CarbonImmutable::parse('2026-09-16 10:00:00', 'UTC'));

    RecordCapture::run(
        User::factory()->create(['timezone' => 'Europe/Amsterdam']),
        'call the dentist tomorrow morning'
    );

    // 09:00 in Amsterdam, which is 07:00 UTC, which is what the column holds.
    expect(Intention::query()->sole()->deadline_at?->toDateTimeString())->toBe('2026-09-17 07:00:00');
});

it("accepts the model's deadline only when the extractor found nothing", function (): void {
    answeredAi(['deadline_at' => '2026-10-02T09:00:00+00:00']);

    RecordCapture::run(User::factory()->create(), 'renew my passport');

    expect(Intention::query()->sole()->deadline_at?->toDateTimeString())->toBe('2026-10-02 09:00:00');
});

it('leaves the capture intact and the intention uncreated when the parse throws', function (): void {
    fakeAi()->respondWith(['title' => '', 'clarifying_question' => null]);

    expect(fn (): mixed => RecordCapture::run(User::factory()->create(), 'sort the thing out'))
        ->toThrow(AiResponseInvalid::class);

    $capture = Capture::query()->sole();

    expect($capture->body)->toBe('sort the thing out')
        ->and($capture->intention_id)->toBeNull()
        ->and($capture->processed_at)->toBeNull()
        ->and(Intention::query()->count())->toBe(0);
});

it('logs a decomposition quality violation against the intention it came from', function (): void {
    // The spy answers channel() with null, which the AI call log would then write to.
    config(['ai-toolkit.log.channel' => null]);
    Log::spy();
    answeredAi(decompose: ['steps' => [['title' => 'Sort out the paperwork.', 'estimated_seconds' => 900]]]);

    RecordCapture::run(User::factory()->create(), 'move apartment');

    $intention = Intention::query()->sole();

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context): bool => $context['intention_id'] === $intention->id
            && $context['violation'] === 'step 1 contains "sort out"')
        ->once();

    expect($intention->steps)->toHaveCount(1);
});

it('keeps where the decomposer said a step has to happen', function (): void {
    answeredAi(decompose: ['steps' => [
        ['title' => 'Buy bin bags.', 'estimated_seconds' => 600, 'place' => 'out'],
        ['title' => 'Grab a bin bag.', 'estimated_seconds' => 30, 'place' => null],
    ]]);

    RecordCapture::run(User::factory()->create(), 'clean the kitchen');

    expect(Intention::query()->sole()->steps()->orderBy('position')->pluck('place')->all())
        ->toBe([Place::Out, null]);
});

it('sorts a capture once, however many times the job runs', function (): void {
    answeredAi();
    $capture = RecordCapture::run(User::factory()->create(), 'clean the apartment')->refresh();

    $again = SortCapture::run($capture);

    expect($again->intention_id)->toBe($capture->intention_id)
        ->and(Intention::query()->count())->toBe(1);
});

it("states the deadline to the decomposer on the person's clock", function (): void {
    $provider = answeredAi(['deadline_at' => '2026-10-02T22:30:00+00:00']);

    RecordCapture::run(
        User::factory()->create(['timezone' => 'Europe/Amsterdam']),
        'renew my passport'
    );

    // 22:30 UTC is half past midnight on the 3rd where they are, and that is the day they hear.
    $provider->assertSent(fn (AiRequest $request): bool => $request->operation === AiOperation::DecomposeIntention->value
        && str_contains($request->user, 'Saturday 3 October 2026 00:30'));
});

it('tells the person on the web that the capture landed', function (): void {
    Queue::fake();

    $this->actingAs(User::factory()->create())
        ->from(route('home'))
        ->post(route('captures.store'), ['body' => 'call the dentist'])
        ->assertRedirect(route('home'))
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Got it. Sorting it out.');
});

it('sorts something owed by someone else into a waiting-for, naming who', function (): void {
    answeredAi(['kind' => 'waiting_for', 'title' => 'The contract', 'waiting_on' => 'John']);

    $capture = RecordCapture::run(User::factory()->create(), 'waiting for John to send the contract')->refresh();
    $waitingFor = WaitingFor::query()->sole();

    expect($capture->kind)->toBe(CaptureKind::WaitingFor)
        ->and($capture->routed_id)->toBe($waitingFor->id)
        ->and($capture->processed_at)->not->toBeNull()
        ->and($capture->kind_confirmed_at)->toBeNull()
        ->and($waitingFor->subject)->toBe('John')
        ->and($waitingFor->note)->toBe('The contract')
        ->and(Intention::query()->count())->toBe(0);
});

it('sorts something they said they would do into an inferred commitment', function (): void {
    answeredAi(['kind' => 'promise', 'title' => 'Send Sarah the photos']);

    $capture = RecordCapture::run(User::factory()->create(), "I'll send Sarah the photos tomorrow")->refresh();
    $commitment = Commitment::query()->sole();

    expect($capture->kind)->toBe(CaptureKind::Promise)
        ->and($capture->routed_id)->toBe($commitment->id)
        ->and($commitment->description)->toBe('Send Sarah the photos')
        ->and($commitment->provenance)->toBe(CommitmentProvenance::SystemInferred)
        ->and($commitment->confirmed_at)->toBeNull();
});

it('sorts an ask to be reminded into a reminder at the time it names', function (): void {
    answeredAi(['kind' => 'reminder', 'title' => 'Call the dentist']);
    $this->travelTo(CarbonImmutable::parse('2026-09-16 10:00:00', 'UTC'));

    $capture = RecordCapture::run(User::factory()->create(), 'remind me tomorrow at 9 to call the dentist')->refresh();
    $reminder = FutureReminder::query()->sole();

    expect($capture->kind)->toBe(CaptureKind::Reminder)
        ->and($capture->routed_id)->toBe($reminder->id)
        ->and($reminder->trigger_at->toDateTimeString())->toBe('2026-09-17 09:00:00');
});

it('keeps a reminder with no time in it as a thought', function (): void {
    answeredAi(['kind' => 'reminder', 'title' => 'Call the dentist']);

    $capture = RecordCapture::run(User::factory()->create(), 'remind me to call the dentist')->refresh();

    expect($capture->kind)->toBe(CaptureKind::Thought)
        ->and($capture->intention_id)->toBe(Intention::query()->sole()->id)
        ->and($capture->routed_id)->toBe($capture->intention_id)
        ->and(FutureReminder::query()->count())->toBe(0);
});

it('reads long text with the classifier and never asks the parse', function (bool $actionable, CaptureKind $kind, int $intentions): void {
    $provider = fakeAi()
        ->respondFor(AiOperation::ClassifyIngestion->value, [
            'actionable' => $actionable,
            'title' => $actionable ? 'Car insurance renewal' : null,
            'why' => $actionable ? 'Policy expires 14 October' : null,
            'deadline_at' => null,
            'estimated_seconds' => $actionable ? 600 : null,
        ])
        ->respondFor(AiOperation::DecomposeIntention->value, ['steps' => [['title' => 'Open the renewal letter.', 'estimated_seconds' => 60]]]);

    $capture = RecordCapture::run(User::factory()->create(), "Dear customer,\n\nYour car insurance policy expires on 14 October.")->refresh();

    expect($capture->kind)->toBe($kind)
        ->and($capture->processed_at)->not->toBeNull()
        ->and($capture->parsed?->title)->toBe($actionable ? 'Car insurance renewal' : "Dear customer,\n\nYour car insurance policy expires on 14 October.")
        ->and(Intention::query()->count())->toBe($intentions);

    $provider->assertNotSent(fn (AiRequest $request): bool => $request->operation === AiOperation::ParseCapture->value);
})->with([
    'actionable' => [true, CaptureKind::Thought, 1],
    'not actionable' => [false, CaptureKind::NotForYou, 0],
]);

it('stores a promise the person chose as stated by them, and confirmed', function (): void {
    answeredAi(['title' => 'Call mum on Sunday']);
    Queue::fake();
    $capture = RecordCapture::run(User::factory()->create(), 'told mum I would call her on Sunday');

    SortCapture::run($capture, CaptureKind::Promise);

    expect($capture->refresh()->kind)->toBe(CaptureKind::Promise)
        ->and($capture->kind_confirmed_at)->not->toBeNull()
        ->and(Commitment::query()->sole()->provenance)->toBe(CommitmentProvenance::UserStated);
});

it('routes again from the stored parse without asking the model', function (): void {
    $provider = answeredAi(['kind' => 'waiting_for', 'title' => 'The contract', 'waiting_on' => 'John']);
    $capture = RecordCapture::run(User::factory()->create(), 'waiting for John to send the contract')->refresh();

    ChangeCaptureKind::run($capture, CaptureKind::Promise);

    expect($capture->refresh()->kind)->toBe(CaptureKind::Promise)
        ->and(Commitment::query()->count())->toBe(1)
        ->and(WaitingFor::query()->count())->toBe(0);

    $provider->assertSentCount(1);
});

it('marks a capture it cannot sort without consent, and stops counting it as sorting', function (): void {
    config()->set('ai-toolkit.driver', 'openai');
    $user = User::factory()->withoutAiConsent()->create();

    // The sync queue rethrows after failing the job; a worker would only record it.
    expect(fn (): Capture => RecordCapture::run($user, 'call the dentist'))->toThrow(AiConsentRequired::class);

    $capture = Capture::query()->sole();

    expect($capture->failed_at)->not->toBeNull()
        ->and($capture->processed_at)->toBeNull()
        ->and(BuildHome::run($user)->sortingCount)->toBe(0);
});

it('sorts what waited once consent is turned on', function (): void {
    answeredAi();
    $user = User::factory()->withoutAiConsent()->create();
    $capture = Capture::factory()->for($user)->create(['body' => 'clean the apartment', 'failed_at' => now()]);

    UpdateAiConsent::run($user, true);

    expect($capture->refresh()->failed_at)->toBeNull()
        ->and($capture->kind)->toBe(CaptureKind::Thought)
        ->and($capture->intention_id)->toBe(Intention::query()->sole()->id);
});

it('leaves a capture still being sorted alone when consent is turned on again', function (): void {
    Queue::fake();
    $user = User::factory()->withoutAiConsent()->create();
    Capture::factory()->for($user)->create(['failed_at' => now()]);
    Capture::factory()->for($user)->create();

    UpdateAiConsent::run($user, true);
    UpdateAiConsent::run($user->refresh(), true);

    Queue::assertPushed(JobDecorator::class, 1);
});
