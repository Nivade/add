"""Git/gh plumbing and the review ledger the hooks share."""

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


def toplevel(path):
    """The checkout holding `path`, walking up to the nearest existing directory so a file not yet written resolves too."""
    if not path:
        return None
    directory = os.path.realpath(path)
    while not os.path.isdir(directory) and directory != os.path.dirname(directory):
        directory = os.path.dirname(directory)
    return git(directory, "rev-parse", "--show-toplevel")


def common_dir(root):
    return git(root, "rev-parse", "--path-format=absolute", "--git-common-dir")


def in_this_repo(root):
    """True when `root` is a checkout of the repo these hooks live in, so a sibling repo is left alone."""
    return common_dir(root) == common_dir(toplevel(__file__))


def session_root(event):
    """The session's checkout of this repo, a worktree's included; CLAUDE_PROJECT_DIR only when cwd is outside any repo."""
    root = toplevel(event.get("cwd")) or toplevel(os.environ.get("CLAUDE_PROJECT_DIR"))
    return root if root and in_this_repo(root) else None


def current_branch(root):
    branch = git(root, "rev-parse", "--abbrev-ref", "HEAD")
    return branch if branch and branch != "HEAD" else None


def fork_point(root, ref="HEAD"):
    return git(root, "merge-base", "origin/main", ref)


def branch_fork_point(root, branch):
    """The fork point `branch` has on the remote, after a fetch, since a local ref can lag GitHub's "Update branch"."""
    git(root, "fetch", "--quiet", "origin", "main")
    git(root, "fetch", "--quiet", "origin", branch)
    return fork_point(root, "origin/" + branch) or fork_point(root, branch)


def ledger_path(root, branch):
    git_dir = common_dir(root)
    if not git_dir:
        return None
    safe_branch = re.sub(r"[^A-Za-z0-9_.-]", "__", branch)
    return os.path.join(git_dir, "claude-review", safe_branch + ".json")


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
    if fork is None:
        return False
    at_fork = [entry for entry in entries if entry.get("fork") == fork]
    previous = None
    for skill in sequence:
        at = latest_at(at_fork, skill)
        if at is None or (previous is not None and at <= previous):
            return False
        previous = at
    return True
