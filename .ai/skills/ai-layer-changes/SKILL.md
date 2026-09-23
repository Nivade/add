---
name: ai-layer-changes
description: Use when adding or changing anything that asks a model a question — a new `AiOperation`, a prompt edit, a JSON schema, a parser, a provider driver, or a fixture. Also use when a test fails with `AiFixtureMissing`, `AiResponseInvalid`, `AiUnavailable` or `AiRateLimited`, when deciding whether a feature should call a model at all, and before adding a field to an AI answer. Trigger on "prompt", "schema", "fixture", "decompose", "parse capture", "split step", "the model returned", "AiProvider", "openai driver", "ai.driver", `backend/app/Support/Ai/**`.
---

# Changing the AI layer

## First, decide whether to ask at all

Everything deterministic stays out of this layer. Next-action selection, time
arithmetic, the session state machine and the `why` string are computed, never
generated. A model is asked only what cannot be derived from what the person
already wrote.

## One seam, three operations

`AiProvider` is the only contract, with one method. A new feature is a new
`AiOperation` case, not a second method and not a second interface. Never send
one payload twice to ask two questions about it.

Adding an operation touches five places, all small:

1. A case on `App\Enums\Ai\AiOperation`.
2. A prompt constant and its `_VERSION` sibling on `App\Support\Ai\Prompts`.
3. A schema class in `Schemas/` with a `VERSION` constant and a static
   `builder(): Closure`.
4. A parser in `Parsers/`, which is the validator.
5. A static factory on `AiRequest` tying those together.

Then the `match` in `CannedAiProvider` stops being exhaustive and PHPStan says
so — that is the reminder to write a canned answer.

An operation may reuse another's schema and parser when the answer has the same
shape. `SplitStep` reuses `DecomposeIntentionSchema` and `DecomposeParser`, with
its own prompt and prompt version.

## The provider never judges its own answer

A provider returns decoded JSON and nothing else. Whether an answer is usable is
the parser's decision, and an unusable one throws `AiResponseInvalid`. A
provider that quietly repaired a bad answer would hide exactly the failure the
eval corpus exists to measure. Do not add a retry, a coercion or a default
inside a driver.

## A missing fixture throws

`FixtureAiProvider` fails when there is no file for the request; so do
`NullAiProvider` and an exhausted `FakeAiProvider`. Returning an empty answer
would enter something nobody said into the corpus and score it as a real
response.

The suite runs on the `fake` driver, so a test that reaches the AI path without
queueing an answer fails rather than silently collecting a canned one. When a
test fails that way, queue the answer — do not switch the test to `canned`.

`canned` is for clicking through the UI. It accepts any input and is never
scored. `fixture` reads `config('ai.fixture_path')`, keyed by
`AiRequest::cacheKey()`.

## Editing a prompt means bumping its version

`AiRequest::cacheKey()` folds the prompt version and the schema version in, so a
one-byte prompt edit must come with a version bump or stored answers produced by
text that no longer exists keep being served. Fixture filenames are that cache
key, so a bump renames every fixture for the operation.

Prompts are otherwise byte-stable: cached input is an order of magnitude
cheaper, and anything varying per call belongs in the user message. `Prompts.php`
is skipped by Rector for this reason — a reformat there would move every cache
key.

## Schemas: strict mode, one definition

`StructuredAgent` carries `#[Strict]`, so a schema violation is rejected rather
than returned as prose. Strict mode requires every key, so an optional field is
a **nullable and required** type, never an absent one.

There is one schema definition, the fluent builder. A raw-array twin of every
schema drifted from its builder in the sibling repo and the parser turned out to
be the real validator both times. Do not reintroduce the second copy.

## Consent is the driver, and the log holds no content

`canned`, `fixture`, `fake` and `null` never leave the machine; `openai` is the
only one that does, and choosing it is the consent. Per-user consent needs a
column and a screen and waits for the first real user.

`LoggingAiProvider` wraps whatever driver resolved and records operation,
provider, model, prompt and schema versions, duration and token counts — never
the prompt and never the answer. A log holding the person's own sentences is a
second copy of the thing being protected. Do not add a flag that claims to
redact input instead.
