"""Shared by review-ledger.py and merge-gate.py: git/gh plumbing, the ledger path, and the required skill order."""

import json
import os
import re
import subprocess

REQUIRED_SEQUENCE = ("code-review", "simplify")


def _run(cmd, cwd):
    try:
        result = subprocess.run(cmd, cwd=cwd, capture_output=True, text=True, timeout=15)
    except (OSError, subprocess.TimeoutExpired):
        return None
    return result.stdout.strip() if result.returncode == 0 else None


def git(root, *args):
    return _run(["git", "-C", root, *args], root)


def gh(root, *args):
    return _run(["gh", *args], root)


def current_branch(root):
    branch = git(root, "rev-parse", "--abbrev-ref", "HEAD")
    return branch if branch and branch != "HEAD" else None


def fork_point(root):
    return git(root, "merge-base", "origin/main", "HEAD")


def ledger_path(root, branch):
    common_dir = git(root, "rev-parse", "--git-common-dir")
    if not common_dir:
        return None
    if not os.path.isabs(common_dir):
        common_dir = os.path.join(root, common_dir)
    safe_branch = re.sub(r"[^A-Za-z0-9_.-]", "__", branch)
    return os.path.join(common_dir, "claude-review", safe_branch + ".json")


def load_entries(path):
    if not path or not os.path.exists(path):
        return []
    try:
        with open(path) as handle:
            return json.load(handle)
    except (ValueError, OSError):
        return []


def append_entry(path, entry):
    entries = load_entries(path)
    entries.append(entry)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w") as handle:
        json.dump(entries, handle, indent=2)


def latest_at(entries, skill):
    ats = [entry["at"] for entry in entries if entry.get("skill") == skill]
    return max(ats) if ats else None


def ran_in_order(entries, fork, sequence=REQUIRED_SEQUENCE):
    """True when every skill in `sequence` ran, at `fork`, each strictly after the one before it."""
    at_fork = [entry for entry in entries if entry.get("fork") == fork]
    previous = None
    for skill in sequence:
        at = latest_at(at_fork, skill)
        if at is None or (previous is not None and at <= previous):
            return False
        previous = at
    return True
