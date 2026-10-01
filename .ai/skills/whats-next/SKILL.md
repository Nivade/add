---
name: whats-next
description: Recommend the next piece of work from the tracker — unblocked tickets, open MRs/PRs that need action, ready issues, issues awaiting triage. Use on "what next", "which ticket next", "what's left", or after a merge.
metadata:
  version: 2.0.0
  gate: claude
  keywords:
    - "\\bwhat'?s? next\\b"
    - '\bwhich (one|issue|ticket)\b.{0,10}\bnext\b'
    - "\\bwhat'?s left\\b"
---

# What's Next

A project skill that owns its own sequencing (a slice table, a roadmap) wins: follow it and stop here.

The tracker is the only source. Refresh the generated plan view for the user, but never read `.ai/plans/`:

```bash
.claude/hooks/lifecycle.sh plans
```

## Gather

- Open issues: `glab issue list -O json` / `gh issue list --json number,title,body,labels`.
- Specs: open issues whose body has `## Problem Statement` and `## User Stories`. Tickets: open issues whose body has a `Part of #<spec>` line. A ticket is blocked when its `Blocked by: #a, #b` line names an issue that is still open.
- Open MRs/PRs: `glab mr list -F json` / `gh pr list --json number,title,headRefName,statusCheckRollup,mergeable`, kept when the pipeline failed or there is a conflict.
- Counts of `needs-triage` and `ready-for-agent` issues.

## Rank

1. An unblocked open ticket, lowest number first within a spec.
2. An open MR/PR with a failed pipeline or a conflict.
3. A `ready-for-agent` `size:S` issue that is not a ticket.
4. A triage pass, when `needs-triage` is non-zero.

## Answer

One recommendation with a one-line reason, two alternates, and the exact command that starts each:

| Item | Command |
| --- | --- |
| A ticket, or a `size:S` issue | `start-work #<n>` |
| A spec with unblocked tickets and no branch yet | `/implement-spec #<spec>` |
| A broken MR/PR | the branch to check out and the failing job |
| Issues awaiting triage | `/triage` |
| A `size:M` issue with no spec | `/to-spec` |

`/implement-spec`, `/triage` and `/to-spec` are the user's to start: name them, never run them. Check: every open ticket, broken MR/PR and ready issue was considered, even the ones not recommended.
