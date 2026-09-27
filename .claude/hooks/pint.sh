#!/usr/bin/env bash
# Claude Code PostToolUse hook: formats the PHP file an agent just edited with the project's own Pint.

project=$(cd -- "${CLAUDE_PROJECT_DIR:-$PWD}" && pwd -P) || exit 0
input=$(cat)

case $input in
    *'.php"'*) ;;
    *) exit 0 ;;
esac

php=$(command -v php)

if command -v jq >/dev/null 2>&1; then
    file=$(printf '%s' "$input" | jq -r '.tool_input.file_path // .tool_input.path // empty')
elif [ -n "$php" ]; then
    file=$(printf '%s' "$input" | php -r '$in = json_decode(stream_get_contents(STDIN)); $path = $in->tool_input->file_path ?? $in->tool_input->path ?? null; echo is_string($path) ? $path : "";')
else
    exit 0
fi

case $file in
    *.php) ;;
    *) exit 0 ;;
esac

case $file in
    /*) ;;
    *) file=$project/$file ;;
esac

[ -f "$file" ] || exit 0

# Resolved, so neither `..` nor a symlink leads out of the project.
file=$(cd -- "$(dirname -- "$file")" && pwd -P)/$(basename -- "$file")

case $file in
    "$project"/*) ;;
    *) exit 0 ;;
esac

app=$(dirname -- "$file")

while [ ! -x "$app/vendor/bin/pint" ]; do
    [ "$app" = "$project" ] && exit 0
    app=$(dirname -- "$app")
done

case $file in
    "$app"/vendor/*) exit 0 ;;
esac

cd -- "$app" || exit 0
relative=${file#"$app"/}

if [ -n "$php" ]; then
    run=vendor/bin/pint
elif [ -f compose.yaml ]; then
    run="vendor/bin/sail pint"
else
    exit 0
fi

# Through stdin, Pint applies pint.json as `composer lint` does: excludes skipped, Blade only with the Blade rule on.
formatted=$(mktemp) || exit 0
trap 'rm -f "$formatted"' EXIT
$run --stdin-filename="$relative" - < "$file" > "$formatted" 2>/dev/null || exit 0
[ -s "$formatted" ] || exit 0
cmp -s "$formatted" "$file" || cat "$formatted" > "$file"

exit 0
