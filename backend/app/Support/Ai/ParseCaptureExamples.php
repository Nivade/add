<?php

declare(strict_types=1);

namespace App\Support\Ai;

use Illuminate\Support\Str;

/** Read out of the prompt itself, so the check cannot drift from the examples the model is shown. */
final class ParseCaptureExamples
{
    /** @var list<string>|null */
    private static ?array $normalisedQuestions = null;

    /** Case and punctuation are ignored, so a copy with a changed comma still counts as one. */
    public static function isCopiedQuestion(?string $question): bool
    {
        if ($question === null) {
            return false;
        }

        return in_array(self::normalise($question), self::normalisedQuestions(), true);
    }

    /** @return list<string> */
    private static function normalisedQuestions(): array
    {
        if (self::$normalisedQuestions === null) {
            preg_match_all('/clarifying_question "([^"]+)"/', Prompts::PARSE_CAPTURE, $examples);
            self::$normalisedQuestions = array_map(self::normalise(...), $examples[1]);
        }

        return self::$normalisedQuestions;
    }

    private static function normalise(string $text): string
    {
        return Str::squish((string) preg_replace('/[^a-z0-9 ]/', '', mb_strtolower($text)));
    }
}
