<?php

declare(strict_types=1);

use App\Support\Time\PhraseDeadlineExtractor;
use Carbon\CarbonImmutable;

// 2026-09-16 is a Wednesday, so every relative phrase below has one unambiguous answer.
function pinnedNow(): CarbonImmutable
{
    return CarbonImmutable::parse('2026-09-16 10:00:00');
}

it('reads every phrase a regex can read with certainty', function (string $text, string $expected): void {
    $extracted = (new PhraseDeadlineExtractor)->extract($text, pinnedNow());

    expect($extracted?->at->toDateTimeString())->toBe($expected);
})->with([
    'coming weekday' => ['clean the apartment before Saturday', '2026-09-19 23:59:59'],
    'weekday with a time of day' => ['call them on Friday morning', '2026-09-18 09:00:00'],
    'tomorrow' => ['post the form tomorrow', '2026-09-17 23:59:59'],
    'tomorrow morning' => ['call the dentist tomorrow morning', '2026-09-17 09:00:00'],
    'tomorrow at a clock time' => ['call the dentist tomorrow at 3pm', '2026-09-17 15:00:00'],
    'tonight' => ['take the bins out tonight', '2026-09-16 21:00:00'],
    'iso date' => ['renew it by 2026-10-02', '2026-10-02 23:59:59'],
    'iso datetime' => ['be there 2026-10-02 08:30', '2026-10-02 08:30:00'],
    'day of month' => ['file it before the 14th', '2026-10-14 23:59:59'],
    'day of month later this month' => ['file it before the 25th', '2026-09-25 23:59:59'],
    'relative days' => ['reply in 3 days', '2026-09-19 23:59:59'],
    'relative hours' => ['reply in two hours', '2026-09-16 12:00:00'],
    'next week' => ['book the van next week', '2026-09-23 23:59:59'],
    'clock time later today' => ['leave at 15:30', '2026-09-16 15:30:00'],
    'clock time already past rolls over' => ['leave at 9am', '2026-09-17 09:00:00'],
]);

it('claims nothing when the text only sounds urgent', function (string $text): void {
    expect((new PhraseDeadlineExtractor)->extract($text, pinnedNow()))->toBeNull();
})->with([
    'I should probably renew my passport',
    'clean the apartment soon',
    'I need to sort this out at some point',
    'buy 3 bags of coffee',
    'look at 2 flats',
]);

it('reads a relative phrase against the zone it was given, not the server\'s', function (): void {
    $extracted = (new PhraseDeadlineExtractor)->extract(
        'call the dentist tomorrow morning',
        CarbonImmutable::parse('2026-09-16 10:00:00', 'Europe/Amsterdam')
    );

    expect($extracted?->at->toDateTimeString())->toBe('2026-09-17 09:00:00')
        ->and($extracted?->at->timezoneName)->toBe('Europe/Amsterdam');
});

it('reports the phrase it claimed so the model is never asked about it again', function (): void {
    $extracted = (new PhraseDeadlineExtractor)->extract(
        'I need to clean the apartment before Saturday because my parents are coming',
        pinnedNow()
    );

    expect($extracted?->phrase)->toBe('before Saturday');
});
