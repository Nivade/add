<?php

declare(strict_types=1);

namespace App\Support\Ai\Parsers;

use App\Data\Ai\ParsedStepData;
use App\Support\Ai\Exceptions\AiResponseInvalid;

/**
 * Structure is rejected; quality is only reported. Rejecting a usable-but-flawed
 * plan leaves the person with nothing, which is the one outcome worth avoiding.
 */
final class DecomposeParser
{
    public const int MAX_STEPS = 6;

    public const int FIRST_STEP_MAX_SECONDS = 300;

    private const array BANNED_PHRASES = ['and', 'organise', 'organize', 'sort out', 'deal with', 'figure out', 'plan'];

    /** Not a parts-of-speech tagger: these are the openers that reliably mean the step describes rather than instructs. */
    private const array NON_VERB_OPENERS = [
        'a', 'an', 'the', 'your', 'my', 'this', 'that', 'it', 'there', 'then',
        'you', 'we', 'first', 'next', 'finally', 'all', 'some', 'any', 'maybe',
    ];

    /**
     * @param  array<string, mixed>  $payload
     * @return list<ParsedStepData>
     */
    public function parse(array $payload): array
    {
        $rows = $payload['steps'] ?? null;

        if (! is_array($rows) || $rows === []) {
            throw new AiResponseInvalid('decompose_intention returned no steps.');
        }

        $steps = [];

        foreach (array_values($rows) as $index => $row) {
            $steps[] = $this->step($row, $index + 1);
        }

        return $steps;
    }

    /**
     * @param  list<ParsedStepData>  $steps
     * @return list<string>
     */
    public function violations(array $steps): array
    {
        $violations = [];

        if (count($steps) > self::MAX_STEPS) {
            $violations[] = count($steps).' steps, at most '.self::MAX_STEPS.' expected';
        }

        if ($steps !== [] && $steps[0]->estimatedSeconds > self::FIRST_STEP_MAX_SECONDS) {
            $violations[] = 'first step is '.$steps[0]->estimatedSeconds.'s, over '.self::FIRST_STEP_MAX_SECONDS.'s';
        }

        foreach ($steps as $index => $step) {
            $position = $index + 1;

            foreach (self::BANNED_PHRASES as $phrase) {
                if (preg_match('/\b'.preg_quote($phrase, '/').'\b/i', $step->title) === 1) {
                    $violations[] = 'step '.$position.' contains "'.$phrase.'"';
                }
            }

            if (! $this->opensWithAnInstruction($step->title)) {
                $violations[] = 'step '.$position.' does not start with a verb';
            }
        }

        return $violations;
    }

    private function step(mixed $row, int $position): ParsedStepData
    {
        if (! is_array($row)) {
            throw new AiResponseInvalid('decompose_intention step '.$position.' is not an object.');
        }

        $title = $row['title'] ?? null;

        if (! is_string($title) || trim($title) === '') {
            throw new AiResponseInvalid('decompose_intention step '.$position.' has no title.');
        }

        $seconds = $row['estimated_seconds'] ?? null;

        if (! is_int($seconds) && (! is_string($seconds) || ! ctype_digit($seconds))) {
            throw new AiResponseInvalid('decompose_intention step '.$position.' has no estimated_seconds.');
        }

        if ((int) $seconds <= 0) {
            throw new AiResponseInvalid('decompose_intention step '.$position.' estimates zero seconds.');
        }

        return new ParsedStepData(title: trim($title), estimatedSeconds: (int) $seconds);
    }

    private function opensWithAnInstruction(string $title): bool
    {
        $opener = strtolower(preg_replace('/[^a-z]/i', '', strtok(trim($title), " \t") ?: ''));

        return $opener !== '' && ! in_array($opener, self::NON_VERB_OPENERS, true) && ! str_ends_with($opener, 'ing');
    }
}
