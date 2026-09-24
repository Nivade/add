## Branch names

This project follows [Conventional Branches](https://conventionalbranch.org), with the Conventional Commits
types added as documented custom types. A husky `pre-push` hook checks the name; a CI job checks it again
on the merge request or pull request, since `--no-verify` skips the hook.

- Format: `type/description`, e.g. `feature/issue-123-new-login`. Ticket numbers are part of the description.
- Trunk branches `main`, `master`, `develop` always pass, unchecked.
- Spec types: `feature`, `feat`, `bugfix`, `fix`, `hotfix`, `release`, `chore`.
- Commit types added as custom types: `refactor`, `docs`, `style`, `perf`, `test`, `build`, `ci`, `revert`.
- Description: lower case `[a-z0-9]` segments joined by single hyphens, e.g. `new-login`. `release/` also
  allows dots: `release/1.2.0`.
- Rejected: underscores, uppercase, spaces, `--`, `.-`, a leading or trailing `-`/`.`, an empty description,
  a nested `/`.
- Deviation from the spec: no AI agent source prefixes — `ai/`, `claude/`, `codex/`, `copilot/`, `cursor/`
  are all rejected. Pick a real type instead, e.g. `feature/add-search` rather than `claude/add-search`.
- `.husky/branch-names` lists this project's own extra types and allowed patterns, if any — read it before
  assuming a name is invalid; the FAQ requires custom types be documented there, not just used.
- When the hook rejects a name, rename with `git branch -m <new-name>`. Never bypass it with `--no-verify`.

### Bot and web-UI branches

Only branches pushed from a clone hit the local hook; bot and web-UI branches reach CI only.

- `dependabot/*` and `renovate/*` are allowed by default in CI.
- A GitHub or GitLab web-UI branch (`<user>-patch-<n>`, `revert-<pr>-<branch>`, `<user>-<branch>-patch-<n>`,
  `revert-<sha>`, `cherry-pick-<sha>`) is not allowed by default: rename it in the "Create a branch" or MR/PR
  dialog before it reaches CI.
- A GitLab issue branch (`<iid>-<title>`) is not allowed by default either: the fix is at the source — set
  Settings > Repository > Branch defaults > Branch name template to `feature/%{id}-%{title}`, or add
  `allow [0-9]*-*` to `.husky/branch-names` for branches created before that change.
- GitHub issue branches (`<n>-<title>`) have no equivalent template setting: edit the name in the
  "Create a branch" dialog instead.
