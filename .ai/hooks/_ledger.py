"""Shared by review-ledger.py and merge-gate.py: git plumbing and the per-branch ledger path."""

import os
import re
import subprocess


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
