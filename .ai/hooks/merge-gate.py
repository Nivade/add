#!/usr/bin/env python3
"""PreToolUse on Bash: refuse `gh pr merge` until this branch's ledger holds a code-review, then a simplify, both at the current fork point.

Only sees an agent's own tool calls — a `gh pr merge` typed by the person in their own shell never reaches a hook.
"""

import json
import os
import re
import subprocess
import sys

MERGE_RE = re.compile(r"\bgh\s+pr\s+merge\b")
PR_NUMBER_RE = re.compile(r"\bgh\s+pr\s+merge\s+(\d+)\b")


def git(root, *args):
    try:
        result = subprocess.run(["git", "-C", root, *args], capture_output=True, text=True, timeout=10)
    except (OSError, subprocess.TimeoutExpired):
        return None
    return result.stdout.strip() if result.returncode == 0 else None


def gh(root, *args):
    try:
        result = subprocess.run(["gh", *args], cwd=root, capture_output=True, text=True, timeout=15)
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
        branch = git(root, "rev-parse", "--abbrev-ref", "HEAD")
    if not branch or branch == "HEAD":
        allow()

    fork = git(root, "merge-base", "origin/main", "HEAD")

    path = ledger_path(root, branch)
    entries = []
    if path and os.path.exists(path):
        try:
            with open(path) as handle:
                entries = json.load(handle)
        except (ValueError, OSError):
            entries = []

    at_fork = [entry for entry in entries if entry.get("fork") == fork]
    review_ats = [entry["at"] for entry in at_fork if entry.get("skill") == "code-review"]
    simplify_ats = [entry["at"] for entry in at_fork if entry.get("skill") == "simplify"]

    if review_ats and simplify_ats and min(review_ats) < max(simplify_ats):
        allow()

    block(
        f"Branch '{branch}' has not run finish-branch at its current fork point. "
        "Run the finish-branch skill (code-review, then simplify) before merging. "
        "A rebase or a merge from main moves the fork point and asks for both again."
    )


main()
