# Slice 5 — home

**State:** done, 2026-09-19 · [the slice table](../executive-function-os.md#slices)

*Spec: [`product-spec.md`](../product-spec.md) §5, §29, §39. Rules:
`product-invariants.md` (no backlog dumps), `api-and-data.md`.*

## The visual direction, and why it is not the starter kit's

Chosen against eight alternatives, and it binds every screen built after this
one.

**A rail, not a sidebar.** The left edge is today, drawn to scale: the hours, a
marker at now, a tick for a deadline falling today, a hairline for how long an
open session has run. Time blindness is the deficit the product exists for, so
time is the structure rather than a field inside a card. It is a shared Inertia
prop because every screen shows the same day.

**No cards.** A card implies a list of things to work through, and this product
shows one thing. Bands are a hairline, a label and an answer.

**Ink and amber on charcoal, dark by default.** The accent lands only on the
thing to do now, and always beside the rail that marks it, so no state is
carried by colour alone. Plex Sans for prose, Plex Mono for anything that
counts — labels, estimates, the rail, the controls.

**The six controls are one size and one weight.** That is "skip weighs what done
weighs" made visible instead of written down.

Reopening any of this needs a new decision. Re-adding a sidebar, a card or a
second accent is not a refactor.

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
