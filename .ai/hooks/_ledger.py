"""Git/gh plumbing and the review ledger the hooks share."""

import functools
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


def checkout(path):
    """(top level, git common dir) of the checkout holding `path`; walks up so a file not yet written resolves too."""
    if not path:
        return None, None
    directory = os.path.realpath(path)
    while not os.path.isdir(directory) and directory != os.path.dirname(directory):
        directory = os.path.dirname(directory)
    found = git(directory, "rev-parse", "--path-format=absolute", "--show-toplevel", "--git-common-dir")
    return tuple(found.split("\n")) if found else (None, None)


@functools.cache
def own_common_dir():
    return checkout(__file__)[1]


def repo_root(path):
    """The checkout of this repo holding `path`, a worktree's included; None for a sibling repo or none at all."""
    top, common_dir = checkout(path)
    return top if top and common_dir == own_common_dir() else None


def session_root(event):
    """The session's checkout of this repo; CLAUDE_PROJECT_DIR only when cwd is outside any repo."""
    for path in (event.get("cwd"), os.environ.get("CLAUDE_PROJECT_DIR")):
        top, common_dir = checkout(path)
        if top:
            return top if common_dir == own_common_dir() else None
    return None


def current_branch(root):
    branch = git(root, "rev-parse", "--abbrev-ref", "HEAD")
    return branch if branch and branch != "HEAD" else None


def fork_point(root, ref="HEAD"):
    return git(root, "merge-base", "origin/main", ref)


def ledger_path(branch):
    safe_branch = re.sub(r"[^A-Za-z0-9_.-]", "__", branch)
    return os.path.join(own_common_dir(), "claude-review", safe_branch + ".json")


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
