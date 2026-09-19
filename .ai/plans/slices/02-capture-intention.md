# Slice 2 — capture → intention

*Spec: [`product-spec.md`](../product-spec.md) §6 (text only), §7, §8, §27.
Live state: [`.ai/RESUME.md`](../../RESUME.md). Rules that bind this slice:
`ai-layer.md`, `domain-model.md`, `product-invariants.md`.*

One typed sentence becomes an intention with steps small enough to start. The
AI layer underneath it is written; nothing calls it yet.

## The pipeline

```
RecordCapture ──▶ capture row, returned immediately
      │
      └─▶ ConvertCaptureToIntention (queued)
                │  DeadlineExtractor  ── deterministic, runs first
                │  ParseCaptureParser ── AI, only for what was not extracted
                └─▶ intention row
                          │
                          └─▶ DecomposeIntention (queued)
                                    └─▶ steps, intentions.decomposed_at stamped
```

Capture returns before anything is parsed. A parse failure costs the thought
nothing — the capture is already durable and the person has already moved on.

## Deterministic before AI

`DeadlineExtractor` reads what a regex can read: an ISO date, "Saturday",
"tomorrow morning", "before the 14th", a bare time. It runs first and its answer
wins. The model is asked only about text the extractor left unclaimed, and its
deadline is only accepted when the extractor found nothing.

This is the §27 enrichment rule applied at the smallest scale: a model is asked
what cannot be derived, and never asked to re-derive what was.

The extractor is a seam worth having because two adapters are real — a
timezone-naive test double and the live `CarbonImmutable` one — and because
every scenario test in slice 3 needs to pin "now" without pinning the clock.

## Where the AI layer plugs in

Provider, request, prompts, schemas and providers exist. Outstanding:

| Piece | Responsibility |
| --- | --- |
| `Parsers/ParseCaptureParser` | decoded JSON → `ParsedCaptureData`, or throw `AiResponseInvalid` |
| `Parsers/DecomposeParser` | decoded JSON → `list<ParsedStepData>`, enforcing the step rules |
| `Actions/Captures/RecordCapture` | write, dispatch, return |
| `Actions/Intentions/ConvertCaptureToIntention` | extractor, then parser, then intention |
| `Actions/Intentions/DecomposeIntention` | parser, then steps, then `decomposed_at` |

The parser is the validator. The provider hands over decoded JSON and forms no
opinion about it — `ai-layer.md` has the reasoning and the test that holds it.

## Step quality is the deliverable

§8 is the slice's real output. A ten-step plan whose first step is "sort out the
paperwork" fails exactly the person this is for. `Prompts::DECOMPOSE` already
states the rules; `DecomposeParser` checks them:

- starts with a verb
- no `and`, `organise`, `sort out`, `deal with`, `figure out`, `plan`
- six steps at most
- first step startable from a chair in under five minutes

A violation is **logged as a quality signal, not rejected**. Rejecting leaves
the person with nothing; logging builds the eval corpus that risk 2 exists for.
`steps.position` is the order the model gave, which is a claim about sequence,
not a priority — slice 3 is free to disagree.

## Done when

- A sentence typed into `POST /api/v1/captures` becomes an intention with usable
  steps, on the `canned` driver, with no API key set.
- A parse that throws leaves the capture intact and the intention uncreated.
- `DeadlineExtractor` beats the model on every case it can read, proven by a
  test where the two disagree.
- Decomposition quality violations appear in the log with the intention id.

## Not this slice

| Spec asks for | Lands in |
| --- | --- |
| Voice, photo, document, email, URL, screenshot capture (§6) | slices 7 and 8 |
| "Remind me tomorrow morning…" becoming a reminder (§7) | slice 6 |
| Project / Task hierarchy (§4) | deferred until it earns its place |
| Clarification dialogue for a vague intention (§7) | `needs_clarification` is parsed and stored now; the conversation that resolves it is slice 4's stuck path |

## Open

Nothing. `needs_clarification` is parsed and stored; slice 3 decided it excludes
an intention at the eligibility stage rather than ranking it low, and slice 5
owns where it surfaces instead.
