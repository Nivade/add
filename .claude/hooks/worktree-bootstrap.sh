#!/usr/bin/env bash
# Claude Code SessionStart hook: fills in what a fresh git worktree lacks (gitignored deps, .env, skills). Idempotent; never fails the session.

project=$(cd -- "${CLAUDE_PROJECT_DIR:-$PWD}" && pwd -P) || exit 0
cd -- "$project" || exit 0

git_dir=$(git rev-parse --path-format=absolute --git-dir 2>/dev/null) || exit 0
common_dir=$(git rev-parse --path-format=absolute --git-common-dir 2>/dev/null) || exit 0
[ "$git_dir" != "$common_dir" ] || exit 0

main=$(dirname -- "$common_dir")
did=()

if [ ! -f backend/.env ] && [ -f "$main/backend/.env" ]; then
    cp "$main/backend/.env" backend/.env && did+=(".env")
fi

if [ ! -d .agents ] && [ -d "$main/.agents" ]; then
    cp -a "$main/.agents" .agents && did+=(".agents")
fi

if [ ! -d .claude/skills ] && [ -d "$main/.claude/skills" ]; then
    cp -a "$main/.claude/skills" .claude/skills && did+=("skills")
fi

if [ ! -d node_modules ]; then
    npm install --no-audit --no-fund >&2 && did+=("npm")
fi

if [ ! -d backend/vendor ]; then
    (cd backend && composer install --no-interaction --prefer-dist >&2) && did+=("composer")
fi

if { [ ! -d backend/resources/js/routes ] || [ ! -d backend/resources/js/actions ]; } && [ -d backend/vendor ]; then
    (cd backend && php artisan wayfinder:generate --with-form >&2) && did+=("wayfinder")
fi

if [ ! -f backend/public/build/manifest.json ] && [ -d node_modules ]; then
    (cd backend && npm run build >&2) && did+=("assets")
fi

[ ${#did[@]} -eq 0 ] || echo "Worktree bootstrapped: ${did[*]}."
echo "Worktree: Sail containers mount the main checkout, so run npm run artisan:host / test:host here, never the sail scripts."

exit 0
