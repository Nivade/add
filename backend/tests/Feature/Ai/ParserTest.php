<?php

declare(strict_types=1);

use App\Support\Ai\Exceptions\AiResponseInvalid;
use App\Support\Ai\Parsers\DecomposeParser;
use App\Support\Ai\Parsers\ParseCaptureParser;

/** @return array<string, mixed> */
function decomposePayload(array $steps): array
{
    return ['steps' => $steps];
}

it('turns a decoded capture answer into typed data', function (): void {
    $parsed = (new ParseCaptureParser)->parse(parsedCapture([
        'why' => 'Parents are coming',
        'deadline_at' => '2026-09-19T23:59:00+00:00',
    ]), 'UTC');

    expect($parsed->title)->toBe('Clean the apartment')
        ->and($parsed->why)->toBe('Parents are coming')
        ->and($parsed->deadlineAt?->toDateTimeString())->toBe('2026-09-19 23:59:00')
        ->and($parsed->clarifyingQuestion)->toBeNull();
});

it('reads a deadline the model left naive on the clock the person reads', function (): void {
    $parsed = (new ParseCaptureParser)->parse(
        parsedCapture(['deadline_at' => '2026-09-19T09:00:00']),
        'Europe/Amsterdam'
    );

    expect($parsed->deadlineAt?->utc()->toDateTimeString())->toBe('2026-09-19 07:00:00');
});

it('keeps the offset a model stated rather than reading it again', function (): void {
    $parsed = (new ParseCaptureParser)->parse(
        parsedCapture(['deadline_at' => '2026-09-19T09:00:00+00:00']),
        'Europe/Amsterdam'
    );

    expect($parsed->deadlineAt?->utc()->toDateTimeString())->toBe('2026-09-19 09:00:00');
});

it('treats an absent deadline as no deadline rather than an error', function (mixed $value): void {
    expect((new ParseCaptureParser)->parse(parsedCapture(['deadline_at' => $value]), 'UTC')->deadlineAt)->toBeNull();
})->with([null, '', '   ', 'null']);

it('rejects a capture answer the application cannot use', function (array $payload): void {
    expect(fn () => (new ParseCaptureParser)->parse($payload, 'UTC'))->toThrow(AiResponseInvalid::class);
})->with([
    'no title' => [['why' => null, 'clarifying_question' => null]],
    'empty title' => [['title' => '   ', 'clarifying_question' => null]],
    'no clarifying_question' => [['title' => 'Clean the apartment']],
    'non-string clarifying_question' => [['title' => 'Clean the apartment', 'clarifying_question' => true]],
    'non-string why' => [['title' => 'Clean the apartment', 'why' => ['a'], 'clarifying_question' => null]],
    'unreadable deadline' => [['title' => 'Clean the apartment', 'deadline_at' => 'whenever', 'clarifying_question' => null]],
]);

it('turns a decoded decomposition into ordered typed steps', function (): void {
    $steps = (new DecomposeParser)->parse(decomposePayload([
        ['title' => 'Grab a bin bag.', 'estimated_seconds' => 30],
        ['title' => 'Fill the dishwasher.', 'estimated_seconds' => '300'],
    ]));

    expect($steps)->toHaveCount(2)
        ->and($steps[0]->title)->toBe('Grab a bin bag.')
        ->and($steps[1]->estimatedSeconds)->toBe(300);
});

it('rejects a decomposition with no usable structure', function (array $payload): void {
    expect(fn () => (new DecomposeParser)->parse($payload))->toThrow(AiResponseInvalid::class);
})->with([
    'no steps key' => [[]],
    'empty steps' => [['steps' => []]],
    'step is not an object' => [['steps' => ['Grab a bin bag.']]],
    'step has no title' => [['steps' => [['estimated_seconds' => 30]]]],
    'step has no estimate' => [['steps' => [['title' => 'Grab a bin bag.']]]],
    'step estimates nothing' => [['steps' => [['title' => 'Grab a bin bag.', 'estimated_seconds' => 0]]]],
]);

it('keeps a flawed plan and reports the flaws instead of rejecting it', function (): void {
    $parser = new DecomposeParser;

    $steps = $parser->parse(decomposePayload([
        ['title' => 'Sit down and read the whole contract.', 'estimated_seconds' => 1800],
        ['title' => 'Sort out the paperwork.', 'estimated_seconds' => 600],
        ['title' => 'The landlord needs telling.', 'estimated_seconds' => 120],
        ['title' => 'Figure out the utilities.', 'estimated_seconds' => 300],
        ['title' => 'Packing the kitchen.', 'estimated_seconds' => 900],
        ['title' => 'Plan the route.', 'estimated_seconds' => 300],
        ['title' => 'Wipe one worktop.', 'estimated_seconds' => 120],
    ]));

    $violations = $parser->violations($steps);

    expect($steps)->toHaveCount(7)
        ->and($violations)->toContain('7 steps, at most 6 expected')
        ->and($violations)->toContain('first step is 1800s, over 300s')
        ->and($violations)->toContain('step 1 contains "and"')
        ->and($violations)->toContain('step 2 contains "sort out"')
        ->and($violations)->toContain('step 3 does not start with a verb')
        ->and($violations)->toContain('step 4 contains "figure out"')
        ->and($violations)->toContain('step 5 does not start with a verb')
        ->and($violations)->toContain('step 6 contains "plan"');
});

it('reports nothing against a plan that follows the rules', function (): void {
    $parser = new DecomposeParser;

    expect($parser->violations($parser->parse(decomposePayload([
        ['title' => 'Grab a bin bag.', 'estimated_seconds' => 30],
        ['title' => 'Put the obvious rubbish in the bag.', 'estimated_seconds' => 300],
        ['title' => 'Wipe one worktop.', 'estimated_seconds' => 120],
    ]))))->toBe([]);
});
