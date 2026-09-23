---
name: calendar-sync
description: Use when touching the calendar surface — `backend/app/Actions/Calendar/**`, `backend/app/Support/Calendar/**`, `backend/app/Data/Calendar/**`, `App\Contracts\CalendarSource`, `App\Contracts\Appointment`, the `calendar_events` table, or `backend/config/calendar.php`. Also use when adding a calendar provider, when an event is stale or disappears, and when deciding whether something belongs on the `Appointment` contract. Trigger on "calendar", "calendar event", "sync", "ics", "google calendar", "appointment", "CalendarSource", "calendar:sync".
---

# Calendar

Read-only in one direction. The calendar tells us what the day holds; we never
write back to it. Nothing is connected by default — a calendar is the largest
privacy surface in the product, so it is opted into and never assumed.

## The two contracts do different jobs

`CalendarSource` is deliberately narrow: it answers what is on the day between
two instants and nothing else, returning `CalendarEventDraftData`. Adding a
provider therefore never reaches into the domain. Implement `name()` and
`between()`, put the class in `app/Support/Calendar/Sources/`, and register the
driver — nothing else changes.

`Appointment` is what backwards planning, the rail and reminders ask. A dated
intention and a calendar event both answer it, so those three features ask one
question rather than branching on which table the thing came from. Put something
on `Appointment` only when both kinds can answer it.

## Syncing

`SyncCalendar` uses `AsObject`, `AsJob` and `AsCommand` — it is reachable as
`calendar:sync`, as a queued job, and directly. It looks ahead
`calendar.horizon_days`, because preparation is a today concern, not a planner.

Two rules in `handle()` that look like bugs and are not:

- **An empty read is not a cleared day.** A source that failed and a genuinely
  empty window are indistinguishable here, so an empty result reaps nothing.
  Reaping on empty would also take the person's stated minutes with it.
- **An event the source stopped reporting is deleted** within the synced window
  for that source. It was moved or cancelled upstream, and keeping it would have
  us plan a day around something nobody is attending.

Events are matched on `user_id` + `source` + `external_id` and stored in UTC.
The source owns the row; we never invent one.

## Stated minutes survive a resync

`statedSeconds()` / `stateSeconds()` on `Appointment` hold what the person told
us a rung of the day costs. That is our data, not the calendar's — a resync
updates title, location and times, and must not clear it.

## Testing

Drivers are `none`, plus the fake and fixture sources under `Sources/`. A
fixture that does not parse throws `CalendarFixtureInvalid` rather than
returning an empty day, for the same reason a missing AI fixture throws.
