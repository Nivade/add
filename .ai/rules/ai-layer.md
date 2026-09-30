---
paths:
  - 'backend/app/Support/Ai/**'
  - 'backend/app/Providers/AiServiceProvider.php'
  - 'backend/config/ai-toolkit.php'
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

The `fixture` driver fails when there is no file for the request. Returning an
empty answer instead would enter something nobody said into the corpus and score
it as a real response. The `null` driver and an unanswered fake fail for the same
reason. A miss also dumps the request, the person's words included, beside where
the answer belongs, which is why `storage/ai-fixtures` is gitignored.

A miss is an `AiUnavailable`, so a job carrying `#[FailOn(AiUnavailable::class)]`
fails at once instead of retrying: no retry writes the missing file. An unknown
`AI_DRIVER` throws on resolve rather than falling back to `null`, because a typo
there is a deployment mistake, not an outage.

Every feature and browser test starts on `AiToolkit::fake()`, so a test that
reaches the AI path without seeding an answer fails rather than silently
collecting a canned one. `canned` is for clicking through the UI, accepts any
input, and is never scored.

## One schema definition, the fluent builder

first-move kept a raw-array twin of every schema and the two drifted; the parser
was the real validator both times. There is one definition here. Do not
reintroduce the second copy.

## Consent is per person, and every call is logged

§28 asks for an explicit, consented, logged path off the machine.
`AiConsent::leavesTheMachine()` lists what stays: `canned`, `fake`, `null`, and
`fixture` unless it records through `AI_FIXTURE_ON_MISS=record:<driver>`, since
the toolkit builds the driver it records through without any wrapper. Every other
driver, including one added later, is wrapped in the toolkit's `GatedAiProvider`.
The gate asks `User::hasConsentedToAi()` for the user in the request's
`rateLimitScope`, which `AiRequests` fills with the asking person's id, so a
request with no person is refused too. A refusal is `AiConsentRequired`, whose
`#[RespondsWith]` carries the sentence the person reads. Registration requires consent,
because sorting what the person writes is the app's job; withdrawing it in
settings stops sorting, and home says so. The evals score a corpus
nobody wrote, so `AiServiceProvider` hands them an ungated OpenAI provider.

Every call is logged on `ai-toolkit.log.channel`, which falls back to the app's
own channel: `null` there switches the audit off silently. The toolkit's events
fire inside the gate, so `AiConsent` fires its failure event for a refusal.

The log records the operation, provider, model, prompt and schema versions,
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
