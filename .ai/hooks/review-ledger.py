#!/usr/bin/env python3
"""PostToolUse on Skill: record that code-review or simplify ran, for merge-gate.py to check."""

import json
import os
import sys
from datetime import datetime, timezone

sys.path.insert(0, os.path.dirname(os.path.realpath(__file__)))
from _ledger import REQUIRED_SEQUENCE, append_entry, current_branch, fork_point, git, ledger_path  # noqa: E402


def main():
    try:
        event = json.load(sys.stdin)
    except (ValueError, OSError):
        sys.exit(0)

    if event.get("tool_name") != "Skill":
        sys.exit(0)

    skill = (event.get("tool_input") or {}).get("skill")
    if skill not in REQUIRED_SEQUENCE:
        sys.exit(0)

    root = os.environ.get("CLAUDE_PROJECT_DIR", "")
    if not root:
        sys.exit(0)

    branch = current_branch(root)
    head = git(root, "rev-parse", "HEAD")
    if not branch or not head:
        sys.exit(0)

    path = ledger_path(root, branch)
    if not path:
        sys.exit(0)

    append_entry(path, {
        "skill": skill,
        "args": (event.get("tool_input") or {}).get("args", ""),
        "head": head,
        "fork": fork_point(root),
        "at": datetime.now(timezone.utc).isoformat(),
    })

    sys.exit(0)


main()
