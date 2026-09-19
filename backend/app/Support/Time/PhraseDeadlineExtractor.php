<?php

declare(strict_types=1);

namespace App\Support\Time;

use App\Contracts\DeadlineExtractor;
use Carbon\CarbonImmutable;
use Closure;

/** Reads only what a regex can read with certainty; anything softer is left for the model. */
final class PhraseDeadlineExtractor implements DeadlineExtractor
{
    private const array HOUR_OF_DAY = [
        'morning' => 9,
        'afternoon' => 14,
        'evening' => 18,
        'night' => 21,
    ];

    private const array WORD_NUMBER = [
        'a' => 1, 'an' => 1, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5,
        'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10,
    ];

    private const string WEEKDAYS = 'monday|tuesday|wednesday|thursday|friday|saturday|sunday';

    private const string TIME_SUFFIX = '(?:\s+(morning|afternoon|evening|night)|\s+at\s+(\d{1,2})(?::(\d{2}))?\s*(am|pm)?)?';

    public function extract(string $text, CarbonImmutable $now): ?ExtractedDeadline
    {
        $haystack = mb_strtolower($text);

        foreach ($this->patterns() as $pattern => $resolve) {
            if (preg_match($pattern, $haystack, $matches, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }

            $at = $resolve($matches, $now);

            if (! $at instanceof CarbonImmutable) {
                continue;
            }

            return new ExtractedDeadline($at, substr($text, (int) $matches[0][1], strlen((string) $matches[0][0])));
        }

        return null;
    }

    /**
     * Ordered most specific first; the first pattern that resolves wins.
     *
     * @return array<string, Closure(array<int, array{0: string, 1: int}>, CarbonImmutable): ?CarbonImmutable>
     */
    private function patterns(): array
    {
        return [
            '/\b(\d{4})-(\d{2})-(\d{2})(?:[t ](\d{1,2}):(\d{2}))?\b/' => $this->isoDate(...),
            '/\bin (\d+|a|an|one|two|three|four|five|six|seven|eight|nine|ten) (minute|hour|day|week|month)s?\b/' => $this->relativeOffset(...),
            '/\b(?:before|by|on|due) the (\d{1,2})(?:st|nd|rd|th)\b/' => $this->dayOfMonth(...),
            '/\b(?:(?:next|this|on|by|before|coming)\s+)?('.self::WEEKDAYS.')'.self::TIME_SUFFIX.'/' => $this->weekday(...),
            '/\b(today|tonight|tomorrow)'.self::TIME_SUFFIX.'/' => $this->namedDay(...),
            '/\bnext (week|month)\b/' => $this->nextPeriod(...),
            '/\bat (\d{1,2})(?::(\d{2}))?\s*(am|pm)?\b/' => $this->clockTime(...),
        ];
    }

    /** @param  array<int, array{0: string, 1: int}>  $matches */
    private function isoDate(array $matches, CarbonImmutable $now): CarbonImmutable
    {
        $day = $now->setDate(
            (int) $this->group($matches, 1),
            (int) $this->group($matches, 2),
            (int) $this->group($matches, 3)
        );

        $hour = $this->group($matches, 4);

        return $hour === null
            ? $day->endOfDay()
            : $day->setTime((int) $hour, (int) $this->group($matches, 5));
    }

    /** @param  array<int, array{0: string, 1: int}>  $matches */
    private function relativeOffset(array $matches, CarbonImmutable $now): CarbonImmutable
    {
        $word = (string) $this->group($matches, 1);
        $count = self::WORD_NUMBER[$word] ?? (int) $word;
        $unit = (string) $this->group($matches, 2);

        $at = $now->add($unit, $count);

        return in_array($unit, ['minute', 'hour'], true) ? $at : $at->endOfDay();
    }

    /** @param  array<int, array{0: string, 1: int}>  $matches */
    private function dayOfMonth(array $matches, CarbonImmutable $now): ?CarbonImmutable
    {
        $day = (int) $this->group($matches, 1);
        $month = $day > $now->day ? $now : $now->addMonth();

        return $day > $month->daysInMonth ? null : $month->setDay($day)->endOfDay();
    }

    /** @param  array<int, array{0: string, 1: int}>  $matches */
    private function weekday(array $matches, CarbonImmutable $now): CarbonImmutable
    {
        return $this->applyTime($now->next((string) $this->group($matches, 1)), $matches);
    }

    /** @param  array<int, array{0: string, 1: int}>  $matches */
    private function namedDay(array $matches, CarbonImmutable $now): CarbonImmutable
    {
        $named = (string) $this->group($matches, 1);

        if ($named === 'tonight') {
            return $now->setTime(self::HOUR_OF_DAY['night'], 0);
        }

        return $this->applyTime($named === 'tomorrow' ? $now->addDay() : $now, $matches);
    }

    /** @param  array<int, array{0: string, 1: int}>  $matches */
    private function nextPeriod(array $matches, CarbonImmutable $now): CarbonImmutable
    {
        return $now->add((string) $this->group($matches, 1), 1)->endOfDay();
    }

    /**
     * A bare hour is too easily a quantity, so a clock time needs minutes or a meridiem.
     *
     * @param  array<int, array{0: string, 1: int}>  $matches
     */
    private function clockTime(array $matches, CarbonImmutable $now): ?CarbonImmutable
    {
        $minute = $this->group($matches, 2);
        $meridiem = $this->group($matches, 3);

        if ($minute === null && $meridiem === null) {
            return null;
        }

        $at = $now->setTime($this->hour((int) $this->group($matches, 1), $meridiem), (int) $minute);

        return $at->lessThanOrEqualTo($now) ? $at->addDay() : $at;
    }

    /** @param  array<int, array{0: string, 1: int}>  $matches */
    private function applyTime(CarbonImmutable $day, array $matches): CarbonImmutable
    {
        $timeOfDay = $this->group($matches, 2);

        if ($timeOfDay !== null) {
            return $day->setTime(self::HOUR_OF_DAY[$timeOfDay], 0);
        }

        $hour = $this->group($matches, 3);

        if ($hour === null) {
            return $day->endOfDay();
        }

        return $day->setTime($this->hour((int) $hour, $this->group($matches, 5)), (int) $this->group($matches, 4));
    }

    private function hour(int $hour, ?string $meridiem): int
    {
        return match ($meridiem) {
            'pm' => $hour === 12 ? 12 : $hour + 12,
            'am' => $hour === 12 ? 0 : $hour,
            default => $hour,
        };
    }

    /** @param  array<int, array{0: string, 1: int}>  $matches */
    private function group(array $matches, int $index): ?string
    {
        $group = $matches[$index] ?? null;

        if ($group === null || $group[1] === -1 || $group[0] === '') {
            return null;
        }

        return $group[0];
    }
}
