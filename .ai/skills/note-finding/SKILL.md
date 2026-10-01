---
name: note-finding
description: File something noticed mid-task that isn't this task's job (a bug, a risk, tech debt, an open question) as a needs-triage issue in the tracker of the repo that owns it. Use when the user says "note this finding", "log this", "file an issue", or when you notice something worth flagging but out of scope for the current change.
metadata:
  version: 2.0.0
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

The repo that owns the cause. When that is an `nvade/*` requirement from `composer.json`, file upstream: `glab issue create -R nvade-packages/<package> ...`. For a package on GitHub, `gh issue create -R <owner>/<repo> ...`. Name the repo you hit it in, since the maintainer cannot see it.

## Permission

Filing publishes. Auto mode blocks `glab issue create`, so when the call is denied, show the title and body and file on the user's yes.

## Triage

`/triage` moves `needs-triage` issues to a role and a size. This skill only files.
