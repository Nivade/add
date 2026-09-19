<?php

declare(strict_types=1);

namespace App\Support\Ai\Providers;

use App\Contracts\AiProvider;
use App\Data\Ai\AiResponseData;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiFixtureMissing;
use App\Support\Ai\Exceptions\AiResponseInvalid;
use Illuminate\Support\Facades\File;

/**
 * Answers from a file instead of an API, so the whole pipeline runs end to end
 * with no credentials and the real parser still judges the answer.
 */
final class FixtureAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'fixture';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public static function path(AiRequest $request): string
    {
        $base = (string) config('ai.fixture_path');

        return rtrim($base, '/').'/'.$request->cacheKey().'.json';
    }

    public function complete(AiRequest $request): AiResponseData
    {
        $path = self::path($request);

        // A missing fixture is an error, never an empty answer: inventing "nothing
        // found" would poison the eval corpus with answers nobody gave.
        if (! File::exists($path)) {
            throw new AiFixtureMissing("No fixture for {$request->operation->value} at {$path}.");
        }

        $decoded = json_decode(File::get($path), true);

        if (! is_array($decoded)) {
            throw new AiResponseInvalid("Fixture {$path} is not a JSON object.");
        }

        return new AiResponseData(
            payload: $decoded,
            provider: $this->name(),
            model: $this->name(),
        );
    }
}
