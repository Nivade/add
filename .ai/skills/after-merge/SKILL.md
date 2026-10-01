---
name: after-merge
description: Clean up once a branch's MR/PR is merged — back on a fresh default branch, branch and worktree removed, finished spec closed, then release or what's next. Use when the user says it is merged, or a hook reports the MR/PR merged.
metadata:
  version: 2.0.0
  gate: claude
  keywords:
    - '\bmerged\b'
---

# After Merge

Never commits: everything a merged branch needed went in before its MR opened.

## 1. Confirm the merge

`glab mr view <n> -F json` shows `"state": "merged"`, or `gh pr view <n> --json state` shows `MERGED`. Find `<n>` with `glab mr list --source-branch <branch> --merged` or `gh pr list --head <branch> --state merged` when nobody named it. Not merged: say so and stop.

Pin `before=$(git rev-parse origin/<default>)` now, before step 2 pulls.

## 2. Back to the default branch

When the branch lives in a worktree, `ExitWorktree` (or `git worktree remove <path>`) first. Then:

```bash
git switch <default>
git pull --ff-only
git branch -d <branch>
```

`git branch -d` refuses a squash-merged branch. Use `git branch -D` only when `git merge-base --is-ancestor <branch> <merged head sha>` succeeds: a reused branch name can hold new work an old MR never saw, and that branch stays. Delete the remote branch only when `git ls-remote --heads origin <branch>` still lists it: `git push origin --delete <branch>`.

Check: on `<default>`, level with `origin/<default>`, `<branch>` gone locally and on the remote.

## 3. Close the spec

The MR's `Closes #<ticket>` lines closed its tickets. Read the ticket's `Part of #<spec>` line, then list the spec's tickets (issues whose body says `Part of #<spec>`). When every one is closed and the spec is still open:

```bash
glab issue close <spec>
gh issue close <spec>
```

A ticket that was its own spec has nothing to close. Check: `glab issue view <spec>` or `gh issue view <spec>` reads closed.

## 4. Refresh the plan view

```bash
.claude/hooks/lifecycle.sh plans
```

## 5. What next

`git log --format=%s "$before"..origin/<default>` lists what the merge brought. When it holds a `feat` or `fix` commit and `.gitlab/ci/release.yml` or `.github/workflows/release.yml` exists, hand off to `release`. Otherwise hand off to `whats-next`. Name the downstream repos that wait on a release, if this repo is a package others consume.
