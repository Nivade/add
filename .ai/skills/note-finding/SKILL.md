---
name: note-finding
description: File something noticed mid-task that isn't this task's job (a bug, a risk, tech debt, an open question) as a needs-triage issue, filed upstream when the maintainer owns the cause's repo, otherwise in this repo's tracker. Use when the user says "note this finding", "log this", "file an issue", or when you notice something worth flagging but out of scope for the current change.
metadata:
  version: 2.1.0
  keywords:
    - '\b(note|log)\b.{0,20}\b(this|that|a)\b.{0,10}\bfinding\b'
    - '\bfile\b.{0,40}\b(on|in|to)\b.{0,10}\bdevtools\b'
    - '\bfile\b.{0,20}\b(an? )?(issue|bug)s?\b'
---

# Note Finding

The tracker holds every finding. Nothing goes in a file in the repo.

## Filing

1. Skip anything the current diff already fixes, and anything that is opinion with no evidence.
2. Search the tracker for the same claim first (`glab issue list --search "<words>"`, `gh issue list --search "<words>"`). Link the existing issue instead of filing a copy.
3. Title: one specific claim a reader can verify. Body: where you hit it (file, command, repo), the symptom, why it matters, a suggested fix when you have one.
4. File it with the `needs-triage` label, plus `bug` when it is a defect:

```bash
glab issue create --title "<claim>" --description "<body>" --label needs-triage
gh issue create --title "<claim>" --body "<body>" --label needs-triage
```

5. Tell the user in one line with the issue URL, then go back to the task.

## Which repo

Upstream filing is allowed only on repos the maintainer owns:

- GitHub/GitLab owners: `nvade`, `Nivade`, `_nvade`, `nvade_`, `nhavandeursen`, `NVADE__`
- GitLab groups: `nvade-packages`, `nvade-apps`

When the repo that owns the cause is under one of those, file there: `glab issue create -R <group>/<repo> ...` or `gh issue create -R <owner>/<repo> ...`. Name the repo you hit it in, since the maintainer cannot see it.

Any other cause, such as a bug in a third-party package, goes in the current repo's tracker. The body names the upstream package, the file and installed version, the symptom, and the workaround this repo carries and where, so the issue tracks when the workaround can go.

Never file an issue, comment, or open an MR/PR on a repo outside that list.

## Permission

Filing publishes. Auto mode blocks `glab issue create`, so when the call is denied, show the title and body and file on the user's yes.

## Triage

`/triage` moves `needs-triage` issues to a role and a size. This skill only files.
