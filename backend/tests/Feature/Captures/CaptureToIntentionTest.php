<?php

declare(strict_types=1);

use App\Actions\Captures\RecordCapture;
use App\Actions\Intentions\ConvertCaptureToIntention;
use App\Contracts\AiProvider;
use App\Enums\IntentionStatus;
use App\Models\Capture;
use App\Models\Intention;
use App\Models\User;
use App\Support\Ai\Exceptions\AiResponseInvalid;
use App\Support\Ai\Providers\FakeAiProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

function useAiDriver(string $driver): AiProvider
{
    config()->set('ai.driver', $driver);
    app()->forgetInstance(AiProvider::class);

    return aiProvider();
}

function fakeAi(): FakeAiProvider
{
    $provider = useAiDriver('fake');

    expect($provider)->toBeInstanceOf(FakeAiProvider::class);

    /** @var FakeAiProvider $provider */
    return $provider;
}

/**
 * @param  array<string, mixed>  $parse
 * @param  array<string, mixed>|null  $decompose
 */
function answeredAi(array $parse = [], ?array $decompose = null): FakeAiProvider
{
    return fakeAi()
        ->push([
            'title' => 'Clean the apartment',
            'why' => null,
            'deadline_at' => null,
            'needs_clarification' => false,
            ...$parse,
        ])
        ->push($decompose ?? ['steps' => [['title' => 'Grab a bin bag.', 'estimated_seconds' => 60]]]);
}

it('turns one typed sentence into an intention with usable steps, with no API key set', function (): void {
    useAiDriver('canned');
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
    useAiDriver('canned');

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
    useAiDriver('canned');

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/captures', ['body' => ''])
        ->assertJsonValidationErrors('body');
});

it('lets the deadline extractor beat the model when the two disagree', function (): void {
    $provider = answeredAi(['deadline_at' => '2030-01-01T09:00:00+00:00']);
    $this->travelTo(CarbonImmutable::parse('2026-09-16 10:00:00'));

    RecordCapture::run(User::factory()->create(), 'clean the apartment before Saturday');

    expect(Intention::query()->sole()->deadline_at?->toDateTimeString())->toBe('2026-09-19 23:59:59')
        ->and($provider->received[0]->user)->toContain('clean the apartment')
        ->and($provider->received[0]->user)->not->toContain('before Saturday');
});

it('tells the model what day it is and which zone to answer in', function (): void {
    $provider = answeredAi();
    $this->travelTo(CarbonImmutable::parse('2026-09-16 23:30:00', 'UTC'));

    RecordCapture::run(
        User::factory()->create(['timezone' => 'Europe/Amsterdam']),
        'renew my passport'
    );

    // Half past midnight in Amsterdam, so the day the model is told is not the server's.
    expect($provider->received[0]->user)->toContain('Thursday 17 September 2026')
        ->and($provider->received[0]->user)->toContain('Europe/Amsterdam');
});

it('reads a deadline only the model found on the person\'s clock', function (): void {
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

it('accepts the model\'s deadline only when the extractor found nothing', function (): void {
    answeredAi(['deadline_at' => '2026-10-02T09:00:00+00:00', 'needs_clarification' => true]);

    RecordCapture::run(User::factory()->create(), 'renew my passport');

    $intention = Intention::query()->sole();

    expect($intention->deadline_at?->toDateTimeString())->toBe('2026-10-02 09:00:00')
        ->and($intention->needs_clarification)->toBeTrue();
});

it('leaves the capture intact and the intention uncreated when the parse throws', function (): void {
    fakeAi()->push(['title' => '', 'needs_clarification' => false]);

    expect(fn () => RecordCapture::run(User::factory()->create(), 'sort the thing out'))
        ->toThrow(AiResponseInvalid::class);

    $capture = Capture::query()->sole();

    expect($capture->body)->toBe('sort the thing out')
        ->and($capture->intention_id)->toBeNull()
        ->and($capture->processed_at)->toBeNull()
        ->and(Intention::query()->count())->toBe(0);
});

it('logs a decomposition quality violation against the intention it came from', function (): void {
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

it('converts a capture once, however many times the job runs', function (): void {
    answeredAi();
    $capture = RecordCapture::run(User::factory()->create(), 'clean the apartment')->refresh();

    $again = ConvertCaptureToIntention::run($capture);

    expect($again->id)->toBe($capture->intention_id)
        ->and(Intention::query()->count())->toBe(1);
});
