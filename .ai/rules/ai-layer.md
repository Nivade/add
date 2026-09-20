---
paths:
  - 'backend/app/Support/Ai/**'
  - 'backend/app/Contracts/AiProvider.php'
  - 'backend/config/ai.php'
---
# AI layer

Everything deterministic stays out of here. Next-action selection, time
arithmetic, the session state machine and the `why` string are computed, never
generated — a model is asked only what cannot be derived.

## One seam, not five named interfaces

The spec (§27) sketches `IntentParser`, `TaskDecomposer`, `CommitmentDetector`,
`DocumentInterpreter` and `EmailInterpreter`. There is one seam here instead:
`AiProvider`, with an `AiOperation` case and a prompt, schema and parser per
operation.

What §27 actually asks for is that the provider be replaceable, and one contract
delivers that for every operation at once — five would each need their own set
of drivers, fixtures and fakes. Adding an operation is an enum case and three
small classes, not a new interface.

## The provider never judges its own answer

A provider returns decoded JSON and nothing else. Whether an answer is usable is
the parser's decision, and an unusable one throws `AiResponseInvalid`. A provider
that quietly repaired a bad answer would hide exactly the failure the eval corpus
exists to measure.

## A missing fixture throws

`FixtureAiProvider` fails when there is no file for the request. Returning an
empty answer instead would enter something nobody said into the corpus and score
it as a real response. `NullAiProvider` and an exhausted `FakeAiProvider` fail
for the same reason.

The suite runs on the `fake` driver, so a test that reaches the AI path without
queueing an answer fails rather than silently collecting a canned one. `canned`
is for clicking through the UI, accepts any input, and is never scored.

## One schema definition, the fluent builder

first-move kept a raw-array twin of every schema and the two drifted; the parser
was the real validator both times. There is one definition here. Do not
reintroduce the second copy.

## Consent is the driver, and every call is logged

§28 asks for an explicit, consented, logged path off the machine. Two thirds of
that is the driver: `canned`, `fixture`, `fake` and `null` never leave, `openai`
is the only one that does, and choosing it is the consent. Per-user consent needs
a column and a screen, and waits for the first real user rather than being
half-built now.

The logging third is `LoggingAiProvider`, which wraps whatever the driver
resolved. It records the operation, provider, model, prompt and schema versions,
duration and token counts — and never the prompt or the answer. A log holding the
person's own sentences is a second copy of the thing being protected, so a config
flag claiming to redact input was deleted rather than left reading like a feature.

Notification payloads are the other half of this surface: `notifications.data`
holds capture-derived titles in cleartext. Encrypting it belongs with the
ingestion work, where the threat model gets written, and not before push ships.

## Prompt and schema versions live in the cache key

`AiRequest::cacheKey()` folds both versions in, so editing a prompt by one byte
invalidates every stored answer rather than serving one produced by text that no
longer exists. Prompts are otherwise byte-stable: anything varying per call
belongs in the user message, where it does not break provider-side caching of the
system prompt.
