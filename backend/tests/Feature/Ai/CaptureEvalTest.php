<?php

declare(strict_types=1);

use App\Support\Ai\ParseCaptureExamples;
use App\Support\Ai\Prompts;
use Illuminate\Support\Facades\File;

it('counts a question as copied when only case and punctuation differ from a prompt example', function (?string $question, bool $copied): void {
    expect(ParseCaptureExamples::isCopiedQuestion($question))->toBe($copied);
})->with([
    'verbatim' => ['Is there a trip you need it for, and when?', true],
    'recased and repunctuated' => ['is there a trip you need it for and when', true],
    'the other example' => ['Which thing do you mean?', true],
    'specific to the capture' => ['Is there a date your licence runs out?', false],
    'no question' => [null, false],
]);

it('keeps the prompt examples in the corpus only as marked controls', function (): void {
    $corpus = json_decode(File::get(storage_path('ai-eval/capture-corpus.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach ($corpus as $entry) {
        expect(str_contains(Prompts::PARSE_CAPTURE, '"'.$entry['capture'].'"'))->toBe(str_ends_with($entry['id'], '-control'), $entry['id']);
    }
});

it('refuses to score without a live key', function (): void {
    config(['ai.openai.api_key' => null]);

    $this->artisan('ai:eval:capture')
        ->expectsOutputToContain('No OPENAI_API_KEY configured')
        ->assertFailed();
});
