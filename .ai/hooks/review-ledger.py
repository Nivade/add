#!/usr/bin/env python3
"""PostToolUse on Skill: record that code-review or simplify ran, for merge-gate.py to check."""

import json
import os
import sys
from datetime import datetime, timezone

sys.path.insert(0, os.path.dirname(os.path.realpath(__file__)))
from _ledger import git, ledger_path  # noqa: E402

TRACKED = ("code-review", "simplify")


def main():
    try:
        event = json.load(sys.stdin)
    except (ValueError, OSError):
        sys.exit(0)

    if event.get("tool_name") != "Skill":
        sys.exit(0)

    skill = (event.get("tool_input") or {}).get("skill")
    if skill not in TRACKED:
        sys.exit(0)

    root = os.environ.get("CLAUDE_PROJECT_DIR", "")
    if not root:
        sys.exit(0)

    branch = git(root, "rev-parse", "--abbrev-ref", "HEAD")
    head = git(root, "rev-parse", "HEAD")
    fork = git(root, "merge-base", "origin/main", "HEAD")
    if not branch or branch == "HEAD" or not head:
        sys.exit(0)

    path = ledger_path(root, branch)
    if not path:
        sys.exit(0)

    entries = []
    if os.path.exists(path):
        try:
            with open(path) as handle:
                entries = json.load(handle)
        except (ValueError, OSError):
            entries = []

    entries.append({
        "skill": skill,
        "args": (event.get("tool_input") or {}).get("args", ""),
        "head": head,
        "fork": fork,
        "at": datetime.now(timezone.utc).isoformat(),
    })

    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w") as handle:
        json.dump(entries, handle, indent=2)

    sys.exit(0)


main()
