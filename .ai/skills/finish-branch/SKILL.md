---
name: finish-branch
description: Run the fixed sequence that turns a branch into an open PR — code-review, then simplify, then the local suite, then push. Use when the person says a branch is ready to merge, done, or asks to finish the branch or open the PR. Does not merge; that stays the person's call after CI.
---

The person's word that a branch is done triggers this, not any commit or file condition. Runs `code-review` then `simplify`, in that order, exactly once each — `merge-gate` (`.ai/hooks/merge-gate.py`) refuses `gh pr merge` on this branch until both are recorded against its current fork point, so skipping a step here only delays it to the merge attempt.

## Steps

1. **Refuse a dirty tree.** `git status --short`. If anything is uncommitted, ask the person whether to commit it or leave it out — do not guess.
2. **Pin the fork point once.** `git fetch origin main` then `git merge-base origin/main HEAD`. Reuse this value for both sub-skills; recomputing mid-run risks the two skills disagreeing about the diff.
3. **`code-review`**, with the fork point as the fixed point. Present its findings to the person, apply the ones they accept, and commit the fixes.
4. **`simplify`**, on `git diff <fork>...HEAD` — the whole branch, not only step 3's commits. Apply and commit.
5. **The local suite**: `npm run test`, `npm run stan`, `npm run lint`, `composer refactor:check`. Fix or report failures before continuing.
6. **Push and open the PR** (`gh pr create`, or update the existing one). No attribution lines.

Stop here. Merging is the person's call, after CI — this skill never runs `gh pr merge`.

## Why the order is fixed

`simplify` after `code-review` means it sees the fixed diff, not a version `code-review` is about to flag. Running them in the other order, or only one, is what `merge-gate` exists to catch — it reads `claude-review/<branch>.json` under the git common dir and checks for a `code-review` entry followed by a `simplify` entry, both stamped with the fork point the branch carries right now. A commit made after `simplify` passes: it is the fix for what `simplify` found, not new unreviewed work. A rebase or a merge from `main` moves the fork point and asks for both again, from this skill, not by chasing the two sub-skills separately.
