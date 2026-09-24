# Agent tooling — finish a branch, and restore vendored skills

**State:** done, 2026-09-24 · [the slice table](executive-function-os.md#slices)

*Rules: `overview.md` (rule, skill, or hook), `general.md`. Skills: `writing-for-agents`,
`code-review`, `update-config`.*

When the person says a branch is done, two passes run over the whole branch diff,
in this order and once each: `code-review`, then `simplify`. Nothing runs them
today, and a merge can skip both.

The trigger is the person, not a commit condition, so this is a procedure with a
backstop rather than a guard test: a skill owns the order, and a hook refuses a
merge the skill never ran on.

Section 5 is separate and smaller: third-party skills stop living in the repo.

## 1. The `finish-branch` skill

`.ai/skills/finish-branch/SKILL.md`, written with `writing-for-agents`, then
mirrored by `npm run boost:update`. It runs when the person says the branch is
ready to merge, and does, in order:

1. Refuse a dirty tree: uncommitted work is committed first, or the person says
   what to leave out.
2. Pin the fork point once: `git merge-base origin/main HEAD`, after a fetch.
3. Invoke `code-review` with that fork point. Present its findings, apply the ones
   the person accepts, and commit them.
4. Invoke `simplify` on `git diff <fork>...HEAD` — the whole branch, not only step
   3's fixes. Apply and commit.
5. `npm run test`, `npm run stan`, `npm run lint`, `composer refactor:check`.
6. Push, then open the PR or update the open one. No attribution lines.

It stops at the PR. Merging stays the person's call, after CI.

## 2. The ledger — a `PostToolUse` hook on `Skill`

`.ai/hooks/review-ledger.py`. When the invoked skill is `code-review` or
`simplify`, it appends `{skill, args, head, fork, at}` to one JSON file per
branch under `claude-review/` in the git common dir. The common dir, so a worktree
and the main checkout read the same ledger; inside `.git`, so it is never committed.

It records the invocation, not the outcome. That matches the ask — both are run —
and is the honest limit of a hook: it sees the call, not what was done with the
findings.

## 3. The gate — a `PreToolUse` hook on `Bash`

`.ai/hooks/merge-gate.py`, matching `gh pr merge`. It resolves the PR's head
branch (`gh pr view --json headRefName`, current branch when no number is given)
and blocks with exit 2 unless that branch's ledger holds:

- a `code-review`, then a later `simplify`,
- both recorded against the fork point the branch has now.

Commits after `simplify` pass: they are its own fixes. A rebase or a merge from
main moves the fork point and asks for both again. The message names
`finish-branch`, not the two skills, so the fix is one step.

## 4. Wiring and pointers

- `.claude/settings.json`: `PostToolUse` matcher `Skill` for the ledger,
  `PreToolUse` matcher `Bash` for the gate, beside the two hooks already there.
- `slice-workflow`'s Finishing section: the command list becomes "finish with
  `finish-branch`", which carries it.
- `skill-roster.py`: `finish-branch` in the roster, and a keyword row for "ready
  to merge", "finish the branch", "open the PR".

## 5. Vendored skills restore from the lockfile

The five Expo skills are the only third-party ones; `skills-lock.json` pins them
by hash. The repo keeps the lock and ignores the copies, the way it keeps
`package-lock.json` and ignores `node_modules`. Skills this repo authors stay
committed: they are its documentation.

- `.gitignore` names the five folders one by one, never a glob over
  `.ai/skills/`, so a new authored skill cannot be ignored by accident. The
  eas-workflows cache rule goes with them.
- A root script runs `npx skills experimental_install`. First confirm where it
  writes: if not `.ai/skills/`, the script moves the result there, because Boost
  mirrors from `.ai/` and the hooks name skills from there.
- `CLAUDE.md`'s root-script block gains that script, as a step after
  `npm install` on a fresh clone.
- CI needs none of them, so nothing changes there.

Chosen over Expo's own Claude Code plugin marketplace, which would bypass `.ai/`,
drop the hash pin, and namespace every name the hooks match on. The restore
command is marked experimental; if it breaks, committing the copies again is the
fallback, not a rewrite.

## Verification

Hooks have no suite here, so each is proven by breaking it (`general.md`):

- `gh pr merge` on a branch with an empty ledger is refused, with the message.
- `simplify` recorded before `code-review` is refused.
- Both in order passes; a rebase onto a moved main is refused again.
- A `gh pr merge` typed by the person in their own shell is untouched — the hook
  only sees an agent's calls.
- A fresh worktree, then the restore script: the five folders match their
  `skills-lock.json` hashes, and `boost:update` mirrors them.

## Out of scope

A merge clicked in GitHub skips all of this. Posting commit statuses for the
"Protect main" ruleset to require was weighed and left out: the person merges when
they decide the branch is done, and a status anyone with a token can post is a
reminder, not a control.
