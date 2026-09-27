<?php

declare(strict_types=1);

namespace App\Support\Ai;

use Illuminate\Support\Str;

/** Read out of the prompt itself, so the check cannot drift from the examples the model is shown. */
final class ParseCaptureExamples
{
    /** Case and punctuation are ignored, so a copy with a changed comma still counts as one. */
    public static function isCopiedQuestion(?string $question): bool
    {
        if ($question === null) {
            return false;
        }

        preg_match_all('/clarifying_question "([^"]+)"/', Prompts::PARSE_CAPTURE, $examples);

        return in_array(self::normalise($question), array_map(self::normalise(...), $examples[1]), true);
    }

    private static function normalise(string $text): string
    {
        return Str::squish((string) preg_replace('/[^a-z0-9 ]/', '', mb_strtolower($text)));
    }
}
