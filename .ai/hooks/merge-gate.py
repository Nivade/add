#!/usr/bin/env python3
"""PreToolUse on Bash: refuse `gh pr merge` until this branch's ledger holds the required skill sequence, at the current fork point."""

import json
import os
import re
import sys

sys.path.insert(0, os.path.dirname(os.path.realpath(__file__)))
from _ledger import current_branch, fork_point, gh, ledger_path, load_entries, ran_in_order  # noqa: E402

MERGE_RE = re.compile(r"\bgh\s+pr\s+merge\b")
PR_NUMBER_RE = re.compile(r"\bgh\s+pr\s+merge\s+(\d+)\b")


def allow():
    sys.exit(0)


def block(message):
    sys.stderr.write(message + "\n")
    sys.exit(2)


def main():
    try:
        event = json.load(sys.stdin)
    except (ValueError, OSError):
        allow()

    if event.get("tool_name") != "Bash":
        allow()

    command = (event.get("tool_input") or {}).get("command") or ""
    if not MERGE_RE.search(command):
        allow()

    root = os.environ.get("CLAUDE_PROJECT_DIR", "")
    if not root:
        allow()

    pr_match = PR_NUMBER_RE.search(command)
    branch = None
    if pr_match:
        branch = gh(root, "pr", "view", pr_match.group(1), "--json", "headRefName", "-q", ".headRefName")
    if not branch:
        branch = current_branch(root)
    if not branch:
        allow()

    fork = fork_point(root)
    entries = load_entries(ledger_path(root, branch))

    if ran_in_order(entries, fork):
        allow()

    block(
        f"Branch '{branch}' has not run finish-branch at its current fork point. "
        "Run the finish-branch skill (code-review, then simplify) before merging. "
        "A rebase or a merge from main moves the fork point and asks for both again."
    )


main()
