<?php

declare(strict_types=1);

use App\Enums\Ai\AiOperation;
use App\Exceptions\AiConsentRequired;
use App\Models\User;
use App\Support\Ai\AiRequests;
use App\Support\Ai\Providers\CannedAiProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Nvade\AiToolkit\AiRequest;
use Nvade\AiToolkit\AiResponse;
use Nvade\AiToolkit\Contracts\AiProvider;
use Nvade\AiToolkit\Exceptions\AiFixtureMissing;
use Nvade\AiToolkit\Exceptions\AiProviderRequestFailed;
use Nvade\AiToolkit\Exceptions\AiResponseInvalid;
use Nvade\AiToolkit\Exceptions\AiResponseTruncated;
use Nvade\AiToolkit\Exceptions\AiUnavailable;
use Nvade\AiToolkit\Facades\AiToolkit;
use Nvade\AiToolkit\Providers\DispatchingAiProvider;
use Nvade\AiToolkit\Providers\FixtureAiProvider;
use Nvade\AiToolkit\Providers\GatedAiProvider;
use Nvade\AiToolkit\Providers\NullAiProvider;
use Nvade\AiToolkit\Providers\OpenAiProvider;
use Nvade\AiToolkit\Providers\StructuredAgent;
use Nvade\AiToolkit\Testing\FakeAiProvider;

function aiRequest(string $capture = 'clean the apartment before Saturday', ?int $userId = 1): AiRequest
{
    return AiRequests::parseCapture($userId, $capture, CarbonImmutable::parse('2026-09-17 09:00', 'UTC'));
}

/** The driver the registry resolved, beneath the event dispatcher every driver is wrapped in. */
function driverBeneath(AiProvider $provider): AiProvider
{
    while ($provider instanceof GatedAiProvider || $provider instanceof DispatchingAiProvider) {
        $provider = $provider->inner;
    }

    return $provider;
}

function aiLog(): TestHandler
{
    config()->set('logging.channels.ai-test', ['driver' => 'monolog', 'handler' => TestHandler::class]);
    config()->set('ai-toolkit.log.channel', 'ai-test');

    /** @var TestHandler */
    return Log::channel('ai-test')->getLogger()->getHandlers()[0];
}

it('resolves the provider named by the driver config', function (string $driver, string $expected): void {
    config()->set('ai-toolkit.driver', $driver);

    expect(driverBeneath(app(AiProvider::class)))->toBeInstanceOf($expected);
})->with([
    ['canned', CannedAiProvider::class],
    ['fixture', FixtureAiProvider::class],
    ['fake', FakeAiProvider::class],
    ['openai', OpenAiProvider::class],
    ['null', NullAiProvider::class],
]);

it('refuses an unknown driver rather than quietly answering with none', function (): void {
    config()->set('ai-toolkit.driver', 'nonsense');

    expect(fn (): AiProvider => app(AiProvider::class))->toThrow(InvalidArgumentException::class);
});

it('gates only a driver that leaves the machine on per-user consent', function (string $driver, string $onMiss, bool $gated): void {
    config()->set('ai-toolkit.driver', $driver);
    config()->set('ai-toolkit.fixture.on_miss', $onMiss);

    expect(app(AiProvider::class) instanceof GatedAiProvider)->toBe($gated);
})->with([
    'openai' => ['openai', 'dump', true],
    'fixture recording through openai' => ['fixture', 'record:openai', true],
    'fixture replaying' => ['fixture', 'dump', false],
    'canned' => ['canned', 'dump', false],
    'fake' => ['fake', 'dump', false],
    'null' => ['null', 'dump', false],
]);

it('gates a driver nobody has said stays on the machine', function (): void {
    AiToolkit::extend('elsewhere', fn (): AiProvider => new class implements AiProvider
    {
        public function name(): string
        {
            return 'elsewhere';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function respond(AiRequest $request): AiResponse
        {
            return new AiResponse(payload: [], provider: 'elsewhere', model: 'elsewhere');
        }
    });
    config()->set('ai-toolkit.driver', 'elsewhere');

    expect(app(AiProvider::class))->toBeInstanceOf(GatedAiProvider::class);
});

it('refuses to answer when AI is disabled rather than returning an empty payload', function (): void {
    expect(fn (): AiResponse => (new NullAiProvider)->respond(aiRequest()))
        ->toThrow(AiUnavailable::class)
        ->and((new NullAiProvider)->isAvailable())->toBeFalse();
});

it('throws on a missing fixture instead of inventing an answer', function (): void {
    $directory = storage_path('framework/testing/ai-fixtures');
    config()->set('ai-toolkit.fixture_path', $directory);

    expect(fn (): AiResponse => AiToolkit::driver('fixture')->respond(aiRequest()))
        ->toThrow(AiFixtureMissing::class);

    File::deleteDirectory($directory);
});

it('serves a fixture keyed on the request cache key', function (): void {
    $directory = storage_path('framework/testing/ai-fixtures');
    config()->set('ai-toolkit.fixture_path', $directory);

    $request = aiRequest();
    File::ensureDirectoryExists($directory);
    File::put(FixtureAiProvider::path($request), json_encode(['title' => 'Clean the apartment']));

    $response = AiToolkit::driver('fixture')->respond($request);

    File::deleteDirectory($directory);

    expect($response->payload)->toBe(['title' => 'Clean the apartment'])
        ->and($response->provider)->toBe('fixture');
});

it('rejects a fixture that is not a JSON object', function (): void {
    $directory = storage_path('framework/testing/ai-fixtures');
    config()->set('ai-toolkit.fixture_path', $directory);

    $request = aiRequest();
    File::ensureDirectoryExists($directory);
    File::put(FixtureAiProvider::path($request), 'not json');

    expect(fn (): AiResponse => AiToolkit::driver('fixture')->respond($request))->toThrow(AiResponseInvalid::class);

    File::deleteDirectory($directory);
});

it('invalidates stored answers when the prompt version moves', function (): void {
    $request = aiRequest();
    $moved = new AiRequest(
        system: $request->system,
        user: $request->user,
        schema: $request->schema,
        promptVersion: $request->promptVersion.'-next',
        schemaVersion: $request->schemaVersion,
        maxOutputTokens: $request->maxOutputTokens,
        operation: $request->operation,
    );

    expect($moved->cacheKey())->not->toBe($request->cacheKey());
});

it('answers every operation from the canned driver so the UI is clickable without credentials', function (AiOperation $operation): void {
    $payload = (new CannedAiProvider)->respond(aiRequest()->withOperation($operation->value))->payload;

    expect($payload)->not->toBeEmpty();
})->with(AiOperation::cases());

it("keeps the person's own words in the canned capture title", function (): void {
    $payload = (new CannedAiProvider)->respond(aiRequest("renew my passport\nand other noise"))->payload;

    expect($payload['title'])->toBe('renew my passport')
        ->and($payload['clarifying_question'])->toBeNull();
});

it('gives back queued answers in order and then fails loudly', function (): void {
    $provider = fakeAi()->respondWith(['title' => 'first'])->respondWith(['title' => 'second']);

    expect($provider->respond(aiRequest())->payload)->toBe(['title' => 'first'])
        ->and($provider->respond(aiRequest())->payload)->toBe(['title' => 'second'])
        ->and(fn (): AiResponse => $provider->respond(aiRequest()))->toThrow(AiUnavailable::class);
});

it('logs the shape of every call and none of the text', function (): void {
    $log = aiLog();
    fakeAi()->respondWith(['title' => 'Renew my passport']);

    $answer = app(AiProvider::class)->respond(aiRequest('renew my passport'));

    expect($answer->provider)->toBe('fake')
        ->and($log->getRecords())->toHaveCount(1);

    $context = $log->getRecords()[0]->context;

    expect($context)->toHaveKeys([
        'operation', 'provider', 'prompt_version', 'schema_version', 'duration_ms', 'model',
        'input_tokens', 'output_tokens', 'cached_input_tokens',
    ])
        ->and($context['operation'])->toBe('parse_capture')
        ->and($context['provider'])->toBe('fake')
        ->and(json_encode($context, JSON_THROW_ON_ERROR))->not->toContain('passport');
});

it('records the failed call and lets the failure through', function (): void {
    $log = aiLog();

    expect(fn (): AiResponse => app(AiProvider::class)->respond(aiRequest()))->toThrow(AiUnavailable::class)
        ->and($log->hasWarningThatPasses(fn (LogRecord $record): bool => $record->context['exception'] === AiUnavailable::class))->toBeTrue();
});

it('rate-limits the openai driver per user, so one burst cannot lock another person out', function (): void {
    config()->set('ai-toolkit.openai.rate_limit.max_attempts', 1);
    RateLimiter::hit(OpenAiProvider::rateLimitKey('1'), 60);

    expect(RateLimiter::tooManyAttempts(OpenAiProvider::rateLimitKey('1'), 1))->toBeTrue()
        ->and(RateLimiter::tooManyAttempts(OpenAiProvider::rateLimitKey('2'), 1))->toBeFalse();
});

it("reaches the openai driver once consent is on record, throttled on the asking person's own key", function (): void {
    config()->set('ai-toolkit.driver', 'openai');
    config()->set('ai.providers.openai.key', 'sk-test');
    StructuredAgent::fake(fn (): array => ['title' => 'Renew my passport']);
    $user = User::factory()->create();

    $answer = app(AiProvider::class)->respond(aiRequest(userId: $user->id));

    expect($answer->payload)->toBe(['title' => 'Renew my passport'])
        ->and(RateLimiter::attempts(OpenAiProvider::rateLimitKey((string) $user->id)))->toBe(1)
        ->and(RateLimiter::attempts(OpenAiProvider::rateLimitKey(null)))->toBe(0);
});

it("refuses to reach a person's words off the machine without their consent, and logs the refusal", function (?bool $consented): void {
    $log = aiLog();
    config()->set('ai-toolkit.driver', 'openai');
    config()->set('ai.providers.openai.key', 'sk-test');
    StructuredAgent::fake(fn (): array => ['title' => 'Renew my passport']);
    $userId = $consented === null ? null : User::factory()->withoutAiConsent()->create()->id;

    expect(fn (): AiResponse => app(AiProvider::class)->respond(aiRequest('renew my passport', $userId)))
        ->toThrow(AiConsentRequired::class)
        ->and($log->hasWarningThatPasses(fn (LogRecord $record): bool => $record->context['exception'] === AiConsentRequired::class
            && $record->context['operation'] === 'parse_capture'
            && $record->context['provider'] === 'openai'
            && ! str_contains(json_encode($record->context, JSON_THROW_ON_ERROR), 'passport')))->toBeTrue();
})->with([
    'no consent on record' => [false],
    'no person on the request' => [null],
]);

it('reports an openai answer cut off at the token limit as truncated', function (): void {
    config()->set('ai.providers.openai.key', 'sk-test');
    Http::fake(['*/responses' => Http::response([
        'id' => 'resp_1',
        'model' => 'gpt-test',
        'status' => 'incomplete',
        'incomplete_details' => ['reason' => 'max_output_tokens'],
        'output' => [['type' => 'message', 'status' => 'incomplete', 'content' => [['type' => 'output_text', 'text' => '{"title":"Ren']]]],
        'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
    ])]);

    expect(fn (): AiResponse => app(OpenAiProvider::class)->respond(aiRequest(userId: 7)))
        ->toThrow(AiResponseTruncated::class);
});

it('names a content-filter stop in the failure, never the provider text', function (int $status, array $body, string $expected): void {
    config()->set('ai.providers.openai.key', 'sk-test');
    Http::fake(['*/responses' => Http::response($body, $status)]);

    expect(fn (): AiResponse => app(OpenAiProvider::class)->respond(aiRequest(userId: 7)))
        ->toThrow(function (AiProviderRequestFailed $failed) use ($expected): void {
            expect($failed->getMessage())->toContain($expected)->not->toContain('passport');
        });
})->with([
    'content filter' => [200, [
        'id' => 'resp_1',
        'model' => 'gpt-test',
        'status' => 'incomplete',
        'incomplete_details' => ['reason' => 'content_filter'],
        'output' => [['type' => 'message', 'status' => 'incomplete', 'content' => [['type' => 'output_text', 'text' => '{"title":"Ren']]]],
        'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
    ], 'content filter'],
    'provider error echoing the prompt' => [400, ['error' => ['message' => 'Your passport renewal prompt was rejected.']], 'OpenAI request failed: '],
]);
