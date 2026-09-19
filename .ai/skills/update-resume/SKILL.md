---
name: update-resume
description: Update .ai/RESUME.md to point at where the build actually stopped, and route this session's decisions to the files that own them. Use at the end of a session, or when asked to update the resume file, record where we stopped, or wrap up.
argument-hint: "Anything the next session needs that the repo cannot say itself"
---

Leave the next agent a true starting point. Not a report of this session — `git log` is the report.

Unlike `handoff` and `claude-handoff`, this writes a tracked file that outlives the conversation. Nothing conversational belongs in it.

## Check the state, do not recall it

The conversation is the least reliable source here. Before writing:

```bash
git -C <repo> log --oneline -5
git -C <repo> status --short
npm run test:host
```

Confirm every path you are about to name exists, or does not, as claimed.

## What RESUME.md may contain

Where the build stopped, and nothing else.

**Write what does not exist yet, not what does.** "There is no `X`, no `Y`, and `Z` does not exist" tells the next agent where to start and stays true until someone builds it. A list of what was finished is stale on the next commit and duplicates `git log`.

Then the next steps, in the order the slice file gives them.

## What belongs somewhere else

| This session produced | It goes in |
| --- | --- |
| Work finished, files added, bugs fixed | `git log` — nowhere else |
| A slice changing state | the slice table in `.ai/plans/executive-function-os.md` |
| Design for a slice not yet built | that slice's file in `.ai/plans/slices/` |
| An invariant or cross-file trap | `.ai/rules/` — only when the user explicitly asks, via `record-rule` |
| Product intent | nowhere; the spec is verbatim and is not edited |

A decision made this session and written nowhere is lost. Route it before writing RESUME.md, not after.

## Never write

- A count of anything — tests, files, errors.
- A status sentence a command answers faster: "the suite is green", "nothing is committed".
- A slice's state, which the plan table owns.
- A decision and its reasoning, which `.ai/rules/` and the slice files own.

## Before finishing

- A slice file whose "Open" question this session answered should say it is answered.
- Every backticked path with a real extension must exist — `tests/Feature/Guards/DocumentationTest.php` fails otherwise, and a path named before it is built needs its `$planned` list.
- Run the suite; that guard covers `.ai/**` and `CLAUDE.md`.
- Report uncommitted work to the user rather than writing it into the file.
