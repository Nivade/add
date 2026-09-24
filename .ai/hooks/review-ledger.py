#!/usr/bin/env python3
"""PostToolUse on Skill: record that code-review or simplify ran, for merge-gate.py to check.

Records the invocation, not the outcome — that is the honest limit of a hook.
"""

import json
import os
import re
import subprocess
import sys
from datetime import datetime, timezone

TRACKED = ("code-review", "simplify")


def git(root, *args):
    try:
        result = subprocess.run(["git", "-C", root, *args], capture_output=True, text=True, timeout=10)
    except (OSError, subprocess.TimeoutExpired):
        return None
    return result.stdout.strip() if result.returncode == 0 else None


def ledger_path(root, branch):
    common_dir = git(root, "rev-parse", "--git-common-dir")
    if not common_dir:
        return None
    if not os.path.isabs(common_dir):
        common_dir = os.path.join(root, common_dir)
    safe_branch = re.sub(r"[^A-Za-z0-9_.-]", "__", branch)
    return os.path.join(common_dir, "claude-review", safe_branch + ".json")


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
