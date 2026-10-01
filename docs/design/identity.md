# Visual identity

Decided by the UX overhaul of 2026-09-30, and binding on both frontends.

The subject is a calm, competent person beside you on a bad day, so every choice aims at legibility first and one memorable thing: the day, drawn.

**Type.** Atkinson Hyperlegible Next for everything; Atkinson Hyperlegible Mono only for clocks, durations and counts. Designed for legibility, which is this product's promise. Never uppercase, never tracked-out labels.

| Role | Size | Line height | Weight |
| --- | --- | --- | --- |
| The one thing | `clamp(2.25rem, 1.6rem + 2.6vw, 3.75rem)` | 1.1 | 600, tracking -0.015em, `text-balance` |
| Lead (why lines, read-backs) | 1.125rem | 1.55 | 400 |
| Body | 1rem | 1.55 | 400 |
| Band heading (a sentence-case question) | 1rem | 1.4 | 600, muted |
| Small (the smallest size anywhere) | 0.875rem | 1.4 | 400 |
| Numeric (mono, `tabular-nums`) | 0.9375rem | 1.4 | 500 |

**Colour.** `packages/shared/src/tokens.ts` holds these, and `DesignTokensTest` guards their contrast and the CSS copy.

| Token | Light | Dark | Use |
| --- | --- | --- | --- |
| paper | `#F2F4F5` | `#0F1B22` | page |
| surface | `#FFFFFF` | `#162530` | dialogs, fields |
| ink | `#172330` | `#E3EBEE` | text |
| muted | `#4F5D6B` | `#9AADB5` | secondary text |
| line | `#D7DDE1` | `#27363F` | decorative hairlines only, never a control's edge |
| field | `#6F808C` | `#6F8793` | control and field borders |
| now | `#0A6B80` | `#5BC3D9` | the thing to do now, and nothing else |
| on-now | `#FFFFFF` | `#0F1B22` | text on `now` |

Day-strip stops, decorative: light dawn `#F4D8C8`, morning `#F6EBC8`, noon `#F4F2E6`, afternoon `#EADFC2`, dusk `#C9C1E6`, night `#2E3A63`; dark dawn `#3B2B31`, morning `#3A3527`, noon `#25313A`, afternoon `#3A3326`, dusk `#2E2946`, night `#121A2E`. Anchored at 06:00, 09:00, 13:00, 17:00, 20:00, 23:00.

**Shape.** Buttons 0.75rem radius, fields 0.5rem, dialogs 1rem. No cards, no shadows, no gradients except the day strip. Bands are separated by space (3rem desktop, 2.5rem phone), not rules.

**Layout.**

```
desktop                                          phone
┌────────┬──────────────────────────────────┐    ┌──────────────────────┐
│06 ▒    │ add        I'm overwhelmed  Capture│   │ add          Capture │
│   ▒    │                                   │    │ ▒▒▒▒▒▓▓░░░░░░░░░ now │
│12:50 ━━│ Find the old passport.            │    │                      │
│   ░░ ~ │ About 4 minutes, so done around   │    │ Find the old         │
│12:54   │ 12:54 if you start now.           │    │ passport.            │
│        │ [ Start ↵ ]   suggested step      │    │ About 4 minutes …    │
│13:30 ─ │                                   │    │ [      Start       ] │
│ leave  │ Why this one?                     │    │ Why this one?        │
└────────┴──────────────────────────────────┘    └──────────────────────┘
```

- Desktop: the day strip is a sticky full-height column, 7.5rem wide; the content column is left-aligned, `max-w-[40rem]`, `ml-[clamp(1.5rem,6vw,6rem)]`; the header sits inside the same column width, so the eye never crosses the screen.
- Phone: the day strip turns horizontal, 2.5rem tall, under the header; content is full width with `px-5`.

**The day strip** (the signature; the rail rebuilt). 06:00 to 24:00. A 0.75rem gradient band at its inner edge; hour labels every two hours in mono small on paper; now as a 2px `now` line with the clock in mono 600; the step block from now to now plus the step's estimate in `now` at 18% with "done ~12:54"; the session block from its start to now in ink at 12%; prep rungs and leave as ink ticks with labels ("find things 13:00", "get ready 13:10", "leave 13:30"); the appointment as a heavier tick with its title. Everything drawn is `aria-hidden`; one visually hidden sentence from `railSummary()` says it all.

**Controls.** Start and Continue are the only filled buttons: `now` background, `on-now` text, 3.5rem tall, lead size, weight 600, with a key hint. Focus controls are six equal outlined buttons, 4rem tall, body 600, each with a one-line hint and a key. Quiet actions are underlined-on-hover text buttons, at least 2.75rem tall.

**Motion.** One neutral beat whenever the step changes, whatever caused it: the new step fades and rises 0.5rem over 300ms. One orchestrated moment on the closing screen: a check that draws itself, then the lines in sequence. Both are off under `prefers-reduced-motion`.

**Voice.** Sentences, not labels joined by middle dots. Titles appear as the person wrote them, never lower-cased.
