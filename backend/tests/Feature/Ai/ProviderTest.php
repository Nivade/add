<?php

declare(strict_types=1);

use App\Contracts\AiProvider;
use App\Enums\Ai\AiOperation;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiFixtureMissing;
use App\Support\Ai\Exceptions\AiResponseInvalid;
use App\Support\Ai\Exceptions\AiUnavailable;
use App\Support\Ai\Prompts;
use App\Support\Ai\Providers\CannedAiProvider;
use App\Support\Ai\Providers\FakeAiProvider;
use App\Support\Ai\Providers\FixtureAiProvider;
use App\Support\Ai\Providers\NullAiProvider;
use App\Support\Ai\Providers\OpenAiProvider;
use App\Support\Ai\Schemas\ParseCaptureSchema;
use Illuminate\Support\Facades\File;

function aiRequest(
    AiOperation $operation = AiOperation::ParseCapture,
    string $user = 'clean the apartment before Saturday',
    string $promptVersion = Prompts::PARSE_CAPTURE_VERSION,
): AiRequest {
    return new AiRequest(
        operation: $operation,
        system: Prompts::PARSE_CAPTURE,
        user: $user,
        schema: ParseCaptureSchema::builder(),
        promptVersion: $promptVersion,
        schemaVersion: ParseCaptureSchema::VERSION,
        maxOutputTokens: 900,
    );
}

it('resolves the provider named by the driver config', function (string $driver, string $expected): void {
    config()->set('ai.driver', $driver);

    expect(app(AiProvider::class))->toBeInstanceOf($expected);
})->with([
    ['canned', CannedAiProvider::class],
    ['fixture', FixtureAiProvider::class],
    ['fake', FakeAiProvider::class],
    ['openai', OpenAiProvider::class],
    ['null', NullAiProvider::class],
    ['nonsense', NullAiProvider::class],
]);

it('refuses to answer when AI is disabled rather than returning an empty payload', function (): void {
    expect(fn () => (new NullAiProvider)->complete(aiRequest()))
        ->toThrow(AiUnavailable::class)
        ->and((new NullAiProvider)->isAvailable())->toBeFalse();
});

it('throws on a missing fixture instead of inventing an answer', function (): void {
    config()->set('ai.fixture_path', storage_path('framework/testing/ai-fixtures'));

    expect(fn () => (new FixtureAiProvider)->complete(aiRequest()))
        ->toThrow(AiFixtureMissing::class);
});

it('serves a fixture keyed on the request cache key', function (): void {
    $directory = storage_path('framework/testing/ai-fixtures');
    config()->set('ai.fixture_path', $directory);

    $request = aiRequest();
    File::ensureDirectoryExists($directory);
    File::put(FixtureAiProvider::path($request), json_encode(['title' => 'Clean the apartment']));

    $response = (new FixtureAiProvider)->complete($request);

    File::deleteDirectory($directory);

    expect($response->payload)->toBe(['title' => 'Clean the apartment'])
        ->and($response->provider)->toBe('fixture');
});

it('rejects a fixture that is not a JSON object', function (): void {
    $directory = storage_path('framework/testing/ai-fixtures');
    config()->set('ai.fixture_path', $directory);

    $request = aiRequest();
    File::ensureDirectoryExists($directory);
    File::put(FixtureAiProvider::path($request), 'not json');

    $complete = fn () => (new FixtureAiProvider)->complete($request);

    expect($complete)->toThrow(AiResponseInvalid::class);

    File::deleteDirectory($directory);
});

it('invalidates stored answers when the prompt version moves', function (): void {
    expect(aiRequest(promptVersion: '1')->cacheKey())
        ->not->toBe(aiRequest(promptVersion: '2')->cacheKey());
});

it('answers every operation from the canned driver so the UI is clickable without credentials', function (AiOperation $operation): void {
    $payload = (new CannedAiProvider)->complete(aiRequest($operation))->payload;

    expect($payload)->not->toBeEmpty();
})->with(AiOperation::cases());

it('keeps the person\'s own words in the canned capture title', function (): void {
    $payload = (new CannedAiProvider)->complete(aiRequest(user: "renew my passport\nand other noise"))->payload;

    expect($payload['title'])->toBe('renew my passport')
        ->and($payload['needs_clarification'])->toBeFalse();
});

it('gives back queued answers in order and then fails loudly', function (): void {
    $provider = new FakeAiProvider;
    $provider->push(['title' => 'first'])->push(['title' => 'second']);

    expect($provider->complete(aiRequest())->payload)->toBe(['title' => 'first'])
        ->and($provider->complete(aiRequest())->payload)->toBe(['title' => 'second'])
        ->and(fn () => $provider->complete(aiRequest()))->toThrow(AiUnavailable::class);
});
