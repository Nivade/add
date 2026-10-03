#!/usr/bin/env bash
# Claude Code hook: names skills whose frontmatter matches the prompt or the edited file. Never blocks.

project=$(cd -- "${CLAUDE_PROJECT_DIR:-$PWD}" && pwd -P) || exit 0
app_dir="backend"
input=$(cat)
cd -- "$project" || exit 0
export CLAUDE_PROJECT_DIR="$project"

# Sail mounts only the app, so in a monorepo its container sees neither .claude/hooks nor .ai/skills.
if command -v php >/dev/null 2>&1; then
    runner=php
elif [ "$app_dir" = . ] && [ -f compose.yaml ] && [ -x vendor/bin/sail ]; then
    runner="vendor/bin/sail php"
else
    exit 0
fi

printf '%s' "$input" | $runner .claude/hooks/skill-hook.php

exit 0
