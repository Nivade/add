#!/usr/bin/env python3
"""PreToolUse on Bash: refuse `gh pr merge` until this branch's ledger holds the required skill sequence, at the current fork point."""

import json
import os
import re
import shlex
import sys

sys.path.insert(0, os.path.dirname(os.path.realpath(__file__)))
from _ledger import branch_fork_point, current_branch, gh, ledger_path, load_entries, ran_in_order, session_root  # noqa: E402

MERGE_RE = re.compile(r"\bgh\s+pr\s+merge\b")
MERGE_WORDS = ["gh", "pr", "merge"]
VALUE_FLAGS = {"-A", "--author-email", "-b", "--body", "-F", "--body-file", "--match-head-commit", "-R", "--repo", "-t", "--subject"}
SHELL_OPERATORS = set(";&|()")


def allow():
    sys.exit(0)


def block(message):
    sys.stderr.write(message + "\n")
    sys.exit(2)


def merge_args(command):
    """The arguments of the first `gh pr merge` run as a command, None when it only appears inside a string; [] when unparseable."""
    lexer = shlex.shlex(command, posix=True, punctuation_chars=True)
    lexer.whitespace_split = True
    try:
        tokens = list(lexer)
    except ValueError:
        return []
    for start in range(len(tokens) - 2):
        if tokens[start:start + 3] == MERGE_WORDS:
            args = tokens[start + 3:]
            ends = [i for i, arg in enumerate(args) if set(arg) <= SHELL_OPERATORS]
            return args[:ends[0]] if ends else args
    return None


def merge_target(args):
    """The PR selector (number, URL or branch) and --repo, wherever they sit among the flags."""
    selector, repo = None, None
    remaining = iter(args)
    for arg in remaining:
        name, has_value, value = arg.partition("=")
        if name in VALUE_FLAGS:
            value = value if has_value else next(remaining, None)
            repo = value if name in ("-R", "--repo") else repo
        elif not arg.startswith("-") and selector is None:
            selector = arg
    return selector, repo


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

    args = merge_args(command)
    if args is None:
        allow()

    root = session_root(event)
    if not root:
        allow()

    selector, repo = merge_target(args)
    if selector:
        repo_args = ["--repo", repo] if repo else []
        branch = gh(root, "pr", "view", selector, *repo_args, "--json", "headRefName", "-q", ".headRefName") or selector
    else:
        branch = current_branch(root)
    if not branch:
        allow()

    fork = branch_fork_point(root, branch)
    entries = load_entries(ledger_path(root, branch))

    if ran_in_order(entries, fork):
        allow()

    block(
        f"Branch '{branch}' has not run finish-branch at its current fork point. "
        "Run the finish-branch skill (code-review, then simplify) before merging. "
        "A rebase or a merge from main moves the fork point and asks for both again."
    )


main()
