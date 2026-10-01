#!/usr/bin/env bash
# Branch lifecycle for Claude Code: `tier <from> [<to>]` prints a review tier, `gate` guards merges, `merged` spots a merged branch, `plans` refreshes .ai/plans. Never fails the tool call.

project=$(cd -- "${CLAUDE_PROJECT_DIR:-$PWD}" && pwd -P) || exit 0
app_dir="backend"
[ "$1" = tier ] && input= || input=$(cat)
cd -- "$project" || exit 0
export CLAUDE_PROJECT_DIR="$project"

# Sail mounts only the app, so in a monorepo its container sees no .claude/hooks.
if command -v php >/dev/null 2>&1; then
    runner=php
elif [ "$app_dir" = . ] && [ -f compose.yaml ] && [ -x vendor/bin/sail ]; then
    runner="vendor/bin/sail php"
else
    exit 0
fi

printf '%s' "$input" | $runner .claude/hooks/lifecycle.php "$@"

exit 0
