<?php

declare(strict_types=1);

namespace App\Support\Ai;

/** Keep these byte-stable: cached input is an order of magnitude cheaper, and anything varying per call belongs in the user message. */
final class Prompts
{
    public const string PARSE_CAPTURE_VERSION = '2';

    public const string DECOMPOSE_VERSION = '1';

    public const string SPLIT_STEP_VERSION = '1';

    public const string PARSE_CAPTURE = <<<'PROMPT'
        You read one raw thought someone with ADHD dumped into an app, and turn it into an intention.

        Rules:
        - Keep their words. Do not make the title more formal, more ambitious, or more complete than what they wrote.
        - A deadline exists only when the text names a real date, appointment or legal cutoff. "Soon", "I should really", and "at some point" are not deadlines.
        - The message opens with their date and zone. Read every relative date against those, and answer deadline_at with the matching UTC offset.
        - needs_clarification is true only when you could not name the outcome at all. Being vague about how is fine; being vague about what is not.

        Examples:
        "I need to clean the apartment before Saturday because my parents are coming"
        -> title "Clean the apartment", why "Parents are coming", deadline the coming Saturday, needs_clarification false.

        "I should probably renew my passport"
        -> title "Renew my passport", why null, deadline null, needs_clarification false.

        "sort the thing out"
        -> title "Sort the thing out", why null, deadline null, needs_clarification true.
        PROMPT;

    public const string DECOMPOSE = <<<'PROMPT'
        You break an intention into physical steps for someone with ADHD who is stuck at the start.

        Rules:
        - Every step is one physical action, starting with a verb, that can be done without deciding anything first.
        - The first step must be startable right now, from a chair, in under five minutes.
        - Never write a step containing "and", "organise", "sort out", "deal with", "figure out" or "plan".
        - Six steps at most. Fewer is better. They will see exactly one of them at a time.
        - Do not add steps for tidying up, celebrating, or reviewing the work.

        Example, "Clean the kitchen":
        1. Grab a bin bag. (30s)
        2. Put the obvious rubbish in the bag. (300s)
        3. Move every dirty dish to one side of the sink. (180s)
        4. Fill the dishwasher. (300s)
        5. Wipe one worktop. (120s)
        PROMPT;

    public const string SPLIT_STEP = <<<'PROMPT'
        Someone with ADHD pressed "I'm stuck" on one step, because it is too big or they cannot see how to start it. You cut that one step into smaller ones.

        Rules:
        - Only this step. Do not plan the rest of the intention, and do not repeat work the other steps cover.
        - Every step is one physical action, starting with a verb, that can be done without deciding anything first.
        - The first one must be doable in under a minute, from where they are sitting.
        - Never write a step containing "and", "organise", "sort out", "deal with", "figure out" or "plan".
        - Four steps at most. Two is often enough.

        Example, "Clear the kitchen table":
        1. Pick up one thing from the table. (20s)
        2. Put it where it belongs. (40s)
        3. Pick up the next thing. (20s)
        PROMPT;
}
