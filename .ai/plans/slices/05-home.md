# Slice 5 — home

*Spec: [`product-spec.md`](../product-spec.md) §5, §29, §39. Rules:
`product-invariants.md` (no backlog dumps), `api-and-data.md`.*

**Scope, not design.** Layout and copy are decided when the slice starts.

## Shape

Four bands, in this order and no others:

1. **Right now** — one action, its estimate, Start.
2. **Immediate context** — what makes right now make sense.
3. **Coming up** — the next real time constraint.
4. **Needs attention** — what will bite if ignored.

The count of everything else is reassurance, not navigation: "47 things, none of
which you need to think about" states the number and offers no way to list it.

Navigation does not make every screen equally prominent (§29). Home and Focus
are the product; Capture is one keystroke from anywhere.

## Done when

The first complete journey runs end to end in a browser: capture → decompose →
one action → Start → Done → next → distracted → welcome back → finish →
progress. That journey is the bar for calling slices 1–5 done, and §39 says it
should feel polished before anything else is built.

Accessibility is a done-when here, not a later pass: keyboard reachable,
screen-reader labelled, focus visible, no colour-only state (§31).

## Open

The resolver already holds a `needs_clarification` intention back at the
eligibility stage, so it can never be the right-now action. What is still open
is the other half: what "Needs attention" offers the person to resolve it with,
and whether answering there is a confirmation or a fresh capture.
