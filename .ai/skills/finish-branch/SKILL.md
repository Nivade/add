---
name: finish-branch
description: Turn a finished branch into an open MR/PR — review sized to the diff, suite green, pushed. Use when the work is done, or on "finish the branch", "push and open an MR/PR", "ready to merge". Stops before merging.
metadata:
  version: 3.0.0
  gate: claude
  keywords:
    - '\bfinish(ing)?\b.{0,20}\bbranch\b'
    - '\b(push|open|create)\b.{0,20}\b(mr|pr|merge request|pull request)s?\b'
    - '\bready to merge\b'
---

# Finish Branch

Six steps, in order. Each ends on its check; the next starts only once it holds.

## 1. Clean tree, fork point

`git status --short` prints nothing. If it prints anything, ask whether to commit it or leave it out.

```bash
git fetch origin <default>
fork=$(git merge-base origin/<default> HEAD)
```

`<default>` is `origin/HEAD`'s target, else a local `main` or `master`. Pin `fork` once and reuse it.

The branch name `feat|fix/<n>-<slug>` gives the ticket `<n>`. Read it with `glab issue view <n>` or `gh issue view <n>`. Its `Part of #<spec>` line names the spec; with none, the ticket is the spec.

## 2. Size the review

When `code-review` already ran this session over `$fork...HEAD` (the last step of `/implement` and `/implement-spec`), reuse its findings and size only the commits after it: `.claude/hooks/lifecycle.sh tier <reviewed HEAD>`. Otherwise:

```bash
.claude/hooks/lifecycle.sh tier "$fork"
```

One JSON line: `tier` (`none`, `docs`, `light`, `full`), `security`, `reason`. Without the script, the tier is `full` and `security` is true.

## 3. Review

| Tier | Run |
| --- | --- |
| `none` | nothing |
| `docs`, `light` | `code-review` |
| `full` | `simplify`, args `<branch>`; apply and commit its fixes; then `code-review` |

When `security` is true, run `security-review` after that.

`code-review` args: `<fork sha>. Spec: #<spec>. Standards: CODING_STANDARDS.md. Launch both sub-agents with subagent_type caveman:cavecrew-reviewer.`

- `simplify` goes first so `code-review` reads the code that will merge, `simplify`'s rewrites included.
- Launch `simplify`'s four review agents and `security-review`'s sub-tasks with `subagent_type: caveman:cavecrew-reviewer`.

Route every finding:

- Standards hard violation, Spec "missing", "partial" or "looks wrong", any `simplify` or `security-review` fix: apply.
- Smell (a judgement call): apply when the fix is local and obvious, otherwise `note-finding`.
- Scope creep: keep it and list it as noted.
- About code the branch did not touch: `note-finding`.

Commit the fixes (`fix:`, `refactor:`), then pin `reviewed=$(git rev-parse --short HEAD)`: the commit the review covers. Check: every finding sits in exactly one list, applied or noted.

## 4. Suite

The project's CI entry point: `composer ci:check` where it exists, else `composer test`, or the scripts its `CLAUDE.md` names. Fix and commit until it exits 0.

When step 4 added commits, `.claude/hooks/lifecycle.sh tier "$reviewed"` sizes them. Anything but `none` goes back through step 3 for that delta, then moves `reviewed` to the new `HEAD`.

## 5. Push and open

```bash
git push -u origin HEAD
glab mr create --target-branch <default> --remove-source-branch --label <label> --title "<conventional header>" --description "<body>"
gh pr create --base <default> --label <label> --title "<conventional header>" --body "<body>"
```

`glab` on a GitLab remote, `gh` on GitHub; on any other remote, push and say there is nowhere to open it. `<label>` is `bug` when the issue carries it, else `enhancement`. The body follows the `pr` template (Summary, Evidence, Merge Danger), then:

- `## Findings`: the applied list and the noted list, each noted item with its issue link.
- `Closes #<ticket>` per ticket this MR finishes.
- `Closes #<spec>` when this MR closes the spec's last open ticket.
- Last line: `Review: <tier> @ <reviewed> — applied <a>, noted <n>`, adding `, security` when it ran. The merge gate re-sizes everything after `<reviewed>`, so later commits never ride on an old review.
- No attribution lines.

## 6. Report and stop

Give the MR/PR link, the tier, and both finding lists. Merging is the user's call; `after-merge` takes over once it is merged.

## After the MR is open

Commits pushed later (a CI fix, review feedback) need review when `lifecycle.sh tier <reviewed>` says so: run step 3 on that delta, step 4, push, then replace the `Review:` line (`glab mr update <n> --description`, `gh pr edit <n> --body`). The merge gate refuses a head that moved past the reviewed commit with anything that needs review.
