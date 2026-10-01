---
name: start-work
description: Start work on a tracker issue on a fresh branch from the default branch. Use on "start issue <n>", "address issue <n>", "pick up #<n>", or "in a worktree".
metadata:
  version: 2.0.0
  gate: claude
  keywords:
    - '\b(start|begin|address|pick up|take)\b.{0,20}(\bissue\b|#\d+)'
    - '\btake\b.{0,40}\bto (a|its) branch\b'
    - '\bstart-work\b'
    - '\bworktree\b'
---

# Start Work

Four steps. Each ends on its check. The input is an issue number.

## 1. The issue

`glab issue view <n>` or `gh issue view <n>`, then classify it:

- Spec: the body has `## Problem Statement` and `## User Stories`. Its tickets are the issues whose body has `Part of #<n>` (`glab issue list --search "Part of #<n>"`).
- Ticket: the body has a `Part of #<spec>` line.
- Brief: anything else. Its body is the spec.

Check: you can name the class and the issue's `Blocked by:` line, if any.

## 2. Gate by size

- A spec with tickets: name `/implement-spec #<n>` as the user's next command and stop. It branches itself.
- A `size:M` or `size:L` issue that is not a ticket and has no tickets: stop, say it needs design first, name `/to-spec` (after `/grill-with-docs`; `/wayfinder` for `size:L`). Do not branch.
- A ticket with an open blocker: name the blocker and stop.
- A `needs-triage` or `needs-info` issue: name `/triage` and stop.

Check: you either stopped with a command named, or the issue is a ticket or a `size:S` brief with no open blocker.

## 3. Branch from a fresh default

Conventional Branches, per the project's `branches` guideline: `feat/<n>-<slug>` or `fix/<n>-<slug>`, lower-case `[a-z0-9]` words joined by single hyphens. Check the name against `.husky/check-branch-name` where it exists.

```bash
git fetch origin <default>
```

- Use `EnterWorktree` when the user asks for a worktree, when `git status --short` prints anything, or when the current branch is not the default and is not merged. It branches from `origin/<default>`.
- Otherwise: `git switch -c <name> origin/<default>`.

Check: `git log -1 --format=%H` equals `git rev-parse origin/<default>` and the current branch is `<name>`.

## 4. Claim and hand off

`glab issue update <n> --assignee @me`, or `gh issue edit <n> --add-assignee @me`. Then name `/implement #<n>` as the user's next command. `implement` is user-only: never call it.
