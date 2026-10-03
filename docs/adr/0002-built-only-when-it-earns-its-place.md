# Built only when it earns its place

The spec describes a product several years wide, and building ahead of the first polished journey is the premature normalisation §24 warns against. So these stay unbuilt on purpose, and their absence is not a gap:

- `Project`, `Task` and `Document` tables. The spec lists them; nothing needs them yet.
- Named preparation items ("your insurance card"): they need somewhere for objects to live.
- Camera capture: it waits for documents.
- Location triggers: they need a consent surface first.
- Registration and password reset on mobile: they stay on the web.
- Lock-screen actions: the session state machine would have to accept a decision taken outside the app, and nothing has asked for that.
- Remembering a travel-time correction across appointments: it needs somewhere for "this place" to live.
- A label on overwhelm mode's suggested step: its why already says what the step costs, and a second label is the noise that screen removes.
- Encrypted notification payloads: settled by ingestion's threat model.
- Outcome numbers anywhere but `metrics:report` in a terminal. A `--json` flag is the whole change, and it waits until someone asks.
- Comparing the check-in trend with the behavioural outcomes: with one person it is an anecdote, so it waits for a second.

## Consequences

Reminders, future reminders and recurring intentions are scheduled from `routes/console.php` and dispatched as jobs, so seeing one outside a test needs both a scheduler and a queue worker running.
